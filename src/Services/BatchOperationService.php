<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Doctrine\ORM\EntityManagerInterface;
use Turkpin\AdminKit\Services\AuthService;

class BatchOperationService
{
    private EntityManagerInterface $entityManager;
    private AuthService $authService;
    private array $operations;

    public function __construct(EntityManagerInterface $entityManager, AuthService $authService)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->operations = [];
        $this->registerDefaultOperations();
    }

    /**
     * Register default batch operations
     */
    private function registerDefaultOperations(): void
    {
        $this->registerOperation('delete', [
            'title' => 'Delete Selected',
            'icon' => 'trash',
            'color' => 'red',
            'confirm' => 'Are you sure you want to delete {count} selected items?',
            'permission' => 'delete',
            'callback' => [$this, 'bulkDelete']
        ]);

        $this->registerOperation('activate', [
            'title' => 'Activate Selected',
            'icon' => 'check',
            'color' => 'green',
            'confirm' => 'Are you sure you want to activate {count} selected items?',
            'permission' => 'update',
            'callback' => [$this, 'bulkActivate']
        ]);

        $this->registerOperation('deactivate', [
            'title' => 'Deactivate Selected',
            'icon' => 'x',
            'color' => 'yellow',
            'confirm' => 'Are you sure you want to deactivate {count} selected items?',
            'permission' => 'update',
            'callback' => [$this, 'bulkDeactivate']
        ]);

        $this->registerOperation('export', [
            'title' => 'Export Selected',
            'icon' => 'download',
            'color' => 'blue',
            'confirm' => null,
            'permission' => 'index',
            'callback' => [$this, 'bulkExport']
        ]);
    }

    /**
     * Register a batch operation
     */
    public function registerOperation(string $name, array $config): void
    {
        $this->operations[$name] = array_merge([
            'title' => ucfirst($name),
            'icon' => 'cursor-click',
            'color' => 'gray',
            'confirm' => null,
            'permission' => 'update',
            'callback' => null
        ], $config);
    }

    /**
     * Get available operations for entity
     */
    public function getAvailableOperations(string $entityName): array
    {
        $available = [];

        foreach ($this->operations as $name => $operation) {
            if ($this->canUserPerformOperation($entityName, $operation['permission'])) {
                $available[$name] = $operation;
            }
        }

        return $available;
    }

    /**
     * Execute batch operation
     */
    public function execute(string $operation, string $entityClass, array $ids, array $params = []): array
    {
        if (!isset($this->operations[$operation])) {
            throw new \InvalidArgumentException("Unknown operation: {$operation}");
        }

        $config = $this->operations[$operation];
        $callback = $config['callback'];

        if (!$callback || !is_callable($callback)) {
            throw new \InvalidArgumentException("Operation {$operation} has no valid callback");
        }

        try {
            $this->entityManager->beginTransaction();
            
            $result = call_user_func($callback, $entityClass, $ids, $params);
            
            $this->entityManager->commit();
            
            return [
                'success' => true,
                'message' => $result['message'] ?? 'Operation completed successfully',
                'affected' => $result['affected'] ?? count($ids)
            ];

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            return [
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage(),
                'affected' => 0
            ];
        }
    }

    /**
     * Bulk delete operation
     */
    public function bulkDelete(string $entityClass, array $ids, array $params = []): array
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $affected = 0;

        foreach ($ids as $id) {
            $entity = $repository->find($id);
            if ($entity) {
                $this->entityManager->remove($entity);
                $affected++;
            }
        }

        $this->entityManager->flush();

        return [
            'message' => "{$affected} items deleted successfully",
            'affected' => $affected
        ];
    }

    /**
     * Bulk activate operation
     */
    public function bulkActivate(string $entityClass, array $ids, array $params = []): array
    {
        return $this->bulkSetActiveStatus($entityClass, $ids, true);
    }

    /**
     * Bulk deactivate operation
     */
    public function bulkDeactivate(string $entityClass, array $ids, array $params = []): array
    {
        return $this->bulkSetActiveStatus($entityClass, $ids, false);
    }

    /**
     * Set active status for multiple entities
     */
    private function bulkSetActiveStatus(string $entityClass, array $ids, bool $active): array
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $affected = 0;

        foreach ($ids as $id) {
            $entity = $repository->find($id);
            if ($entity && method_exists($entity, 'setIsActive')) {
                $entity->setIsActive($active);
                $affected++;
            }
        }

        $this->entityManager->flush();

        $status = $active ? 'activated' : 'deactivated';
        return [
            'message' => "{$affected} items {$status} successfully",
            'affected' => $affected
        ];
    }

    /**
     * Bulk export operation
     */
    public function bulkExport(string $entityClass, array $ids, array $params = []): array
    {
        $format = $params['format'] ?? 'csv';
        $repository = $this->entityManager->getRepository($entityClass);
        
        $entities = $repository->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // Here you would integrate with ExportService
        // For now, just return success
        return [
            'message' => count($entities) . " items prepared for export",
            'affected' => count($entities),
            'export_url' => "/admin/export/{$format}/" . implode(',', $ids)
        ];
    }

    /**
     * Check if user can perform operation
     */
    private function canUserPerformOperation(string $entityName, string $permission): bool
    {
        try {
            return $this->authService->canAccess($entityName, $permission);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get batch operations JavaScript
     */
    public function getBatchScript(): string
    {
        return "
        <script>
        class BatchOperations {
            constructor() {
                this.selectedIds = new Set();
                this.init();
            }

            init() {
                this.bindEvents();
                this.updateUI();
            }

            bindEvents() {
                // Select all checkbox
                const selectAll = document.getElementById('select-all');
                if (selectAll) {
                    selectAll.addEventListener('change', (e) => {
                        this.toggleAll(e.target.checked);
                    });
                }

                // Individual checkboxes
                document.querySelectorAll('.row-select').forEach(checkbox => {
                    checkbox.addEventListener('change', (e) => {
                        this.toggleRow(e.target.value, e.target.checked);
                    });
                });

                // Batch action buttons
                document.querySelectorAll('.batch-action').forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.executeBatchAction(button.dataset.action, button.dataset.confirm);
                    });
                });
            }

            toggleAll(checked) {
                document.querySelectorAll('.row-select').forEach(checkbox => {
                    checkbox.checked = checked;
                    this.toggleRow(checkbox.value, checked);
                });
            }

            toggleRow(id, checked) {
                if (checked) {
                    this.selectedIds.add(id);
                } else {
                    this.selectedIds.delete(id);
                }
                this.updateUI();
            }

            updateUI() {
                const count = this.selectedIds.size;
                const batchContainer = document.getElementById('batch-actions');
                const selectionInfo = document.getElementById('selection-info');
                
                if (batchContainer) {
                    batchContainer.style.display = count > 0 ? 'flex' : 'none';
                }
                
                if (selectionInfo) {
                    selectionInfo.textContent = count > 0 ? count + ' items selected' : '';
                }

                // Update select all checkbox
                const selectAll = document.getElementById('select-all');
                if (selectAll) {
                    const totalRows = document.querySelectorAll('.row-select').length;
                    selectAll.checked = count === totalRows && count > 0;
                    selectAll.indeterminate = count > 0 && count < totalRows;
                }
            }

            executeBatchAction(action, confirmMessage) {
                if (this.selectedIds.size === 0) {
                    alert('Please select at least one item');
                    return;
                }

                if (confirmMessage) {
                    const message = confirmMessage.replace('{count}', this.selectedIds.size);
                    if (!confirm(message)) {
                        return;
                    }
                }

                // Submit form with selected IDs
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.pathname + '/batch/' + action;

                this.selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            new BatchOperations();
        });
        </script>
        ";
    }

    /**
     * Render batch operations UI
     */
    public function renderBatchUI(string $entityName): string
    {
        $operations = $this->getAvailableOperations($entityName);
        
        if (empty($operations)) {
            return '';
        }

        $html = '<div id="batch-actions" class="hidden flex items-center space-x-2 bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">';
        $html .= '<span id="selection-info" class="text-sm font-medium text-blue-800"></span>';
        
        foreach ($operations as $name => $operation) {
            $colorClasses = $this->getColorClasses($operation['color']);
            $confirmAttr = $operation['confirm'] ? 'data-confirm="' . htmlspecialchars($operation['confirm']) . '"' : '';
            
            $html .= "<button class=\"batch-action inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white {$colorClasses} hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-offset-2\" data-action=\"{$name}\" {$confirmAttr}>";
            $html .= "<i class=\"icon-{$operation['icon']} mr-2\"></i>";
            $html .= $operation['title'];
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get color classes for operation
     */
    private function getColorClasses(string $color): string
    {
        $classes = [
            'red' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
            'green' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
            'blue' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
            'yellow' => 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
            'gray' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
        ];

        return $classes[$color] ?? $classes['gray'];
    }
}
