<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Doctrine\ORM\EntityManagerInterface;
use Turkpin\AdminKit\Entities\User;
use Turkpin\AdminKit\Entities\Role;
use Turkpin\AdminKit\Entities\Permission;

class AuthService
{
    private EntityManagerInterface $entityManager;
    private array $config;
    private ?User $currentUser = null;

    public function __construct(EntityManagerInterface $entityManager, array $config)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function authenticate(string $email, string $password): bool
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email, 'isActive' => true]);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->getPassword())) {
            return false;
        }

        $this->setCurrentUser($user);
        $this->startSession($user);

        return true;
    }

    public function logout(): void
    {
        $this->currentUser = null;
        $this->destroySession();
    }

    public function isAuthenticated(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    public function getCurrentUser(): ?User
    {
        if ($this->currentUser === null) {
            $this->loadUserFromSession();
        }

        return $this->currentUser;
    }

    public function setCurrentUser(User $user): void
    {
        $this->currentUser = $user;
    }

    public function hasRole(string $roleName): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user->hasRole($roleName);
    }

    public function hasPermission(string $permissionName): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        foreach ($user->getRoles() as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if (!$this->hasRole($roleName)) {
                return false;
            }
        }

        return true;
    }

    public function canAccess(string $resource, string $action): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Super admin her şeye erişebilir
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Specific permission kontrolü
        $permissionName = $resource . '.' . $action;
        if ($this->hasPermission($permissionName)) {
            return true;
        }

        // Wildcard permission kontrolü
        $wildcardPermission = $resource . '.*';
        if ($this->hasPermission($wildcardPermission)) {
            return true;
        }

        return false;
    }

    public function createUser(array $userData): User
    {
        $user = new User();
        $user->setName($userData['name']);
        $user->setEmail($userData['email']);
        $user->setPassword($this->hashPassword($userData['password']));
        
        if (isset($userData['isActive'])) {
            $user->setIsActive($userData['isActive']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(User $user, array $userData): User
    {
        if (isset($userData['name'])) {
            $user->setName($userData['name']);
        }

        if (isset($userData['email'])) {
            $user->setEmail($userData['email']);
        }

        if (isset($userData['password']) && !empty($userData['password'])) {
            $user->setPassword($this->hashPassword($userData['password']));
        }

        if (isset($userData['isActive'])) {
            $user->setIsActive($userData['isActive']);
        }

        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $user;
    }

    public function assignRole(User $user, Role $role): void
    {
        $user->addRole($role);
        $this->entityManager->flush();
    }

    public function removeRole(User $user, Role $role): void
    {
        $user->removeRole($role);
        $this->entityManager->flush();
    }

    public function createRole(string $name, ?string $description = null): Role
    {
        $role = new Role();
        $role->setName($name);
        
        if ($description) {
            $role->setDescription($description);
        }

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }

    public function createPermission(string $name, ?string $description = null, ?string $resource = null, ?string $action = null): Permission
    {
        $permission = new Permission();
        $permission->setName($name);
        
        if ($description) {
            $permission->setDescription($description);
        }
        
        if ($resource) {
            $permission->setResource($resource);
        }
        
        if ($action) {
            $permission->setAction($action);
        }

        $this->entityManager->persist($permission);
        $this->entityManager->flush();

        return $permission;
    }

    public function assignPermissionToRole(Role $role, Permission $permission): void
    {
        $role->addPermission($permission);
        $this->entityManager->flush();
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private function startSession(User $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['admin_user_id'] = $user->getId();
        $_SESSION['admin_user_email'] = $user->getEmail();
        $_SESSION['admin_login_time'] = time();
    }

    private function loadUserFromSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_user_id'])) {
            return;
        }

        $userId = $_SESSION['admin_user_id'];
        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($userId);

        if ($user && $user->isActive()) {
            $this->currentUser = $user;
        } else {
            $this->destroySession();
        }
    }

    private function destroySession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_user_email']);
        unset($_SESSION['admin_login_time']);
        
        // Session'u tamamen temizle
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
