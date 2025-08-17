<?php

/**
 * User Model Class
 * Handles user authentication and user-related operations
 */
class User {
    
    /**
     * Authenticate user credentials
     * @param string $username
     * @param string $password
     * @param string $portal
     * @param string $securityToken
     * @return array|false User data on success, false on failure
     */
    public static function authenticate($username, $password, $portal = 'default', $securityToken = '') {
        try {
            $db = DB::getInstance();
            
            // For super admin portal, check security token first
            if ($portal === 'saportal') {
                if (!self::validateSecurityToken($securityToken)) {
                    return false;
                }
            }
            
            // Find user by username or email
            $sql = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.password_hash,
                    r.name as role,
                    u.company_id,
                    u.is_active,
                    u.last_login,
                    c.name as company_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE (u.email = :username OR u.username = :username)
                AND u.is_active = 1
            ";
            
            // For super admin portal, only allow super admin role
            if ($portal === 'saportal') {
                $sql .= " AND r.name = 'super_admin'";
            }
            
            $user = $db->queryOne($sql, [':username' => $username]);
            
            if (!$user) {
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }
            
            // Update last login
            self::updateLastLogin($user['id']);
            
            // Return user data (excluding password hash)
            unset($user['password_hash']);
            return $user;
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate security token for super admin access
     * @param string $token
     * @return bool
     */
    private static function validateSecurityToken($token) {
        // In a real implementation, this would check against a secure token
        // For demo purposes, we'll use a simple check
        $validTokens = [
            'rce-admin-2024',
            'luxury-talent-sa',
            'red-carpet-admin'
        ];
        
        return in_array($token, $validTokens);
    }
    
    /**
     * Update user's last login timestamp
     * @param int $userId
     */
    private static function updateLastLogin($userId) {
        try {
            $db = DB::getInstance();
            $db->execute(
                "UPDATE users SET last_login = NOW() WHERE id = :id",
                [':id' => $userId]
            );
        } catch (Exception $e) {
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }
    
    /**
     * Create new user
     * @param array $userData
     * @return int|false User ID on success, false on failure
     */
    public static function create($userData) {
        try {
            $db = DB::getInstance();
            
            // Validate required fields
            $required = ['name', 'email', 'password', 'role_id'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    throw new Exception("Required field missing: {$field}");
                }
            }
            
            // Check if email already exists
            $existing = $db->queryOne(
                "SELECT id FROM users WHERE email = :email",
                [':email' => $userData['email']]
            );
            
            if ($existing) {
                throw new Exception("Email already exists");
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Generate username if not provided
            $username = $userData['username'] ?? self::generateUsername($userData['email']);
            
            // Insert user
            $sql = "
                INSERT INTO users (
                    name, email, username, password_hash, role_id, 
                    company_id, is_active, created_at
                ) VALUES (
                    :name, :email, :username, :password_hash, :role_id,
                    :company_id, :is_active, NOW()
                )
            ";
            
            $params = [
                ':name' => $userData['name'],
                ':email' => $userData['email'],
                ':username' => $username,
                ':password_hash' => $passwordHash,
                ':role_id' => $userData['role_id'],
                ':company_id' => $userData['company_id'] ?? null,
                ':is_active' => $userData['is_active'] ?? 1
            ];
            
            $db->execute($sql, $params);
            return $db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public static function getById($userId) {
        try {
            $db = DB::getInstance();
            
            $sql = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.username,
                    r.name as role,
                    u.company_id,
                    u.is_active,
                    u.created_at,
                    u.last_login,
                    c.name as company_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE u.id = :id
            ";
            
            return $db->queryOne($sql, [':id' => $userId]);
            
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user data
     * @param int $userId
     * @param array $userData
     * @return bool
     */
    public static function update($userId, $userData) {
        try {
            $db = DB::getInstance();
            
            $allowedFields = ['name', 'email', 'username', 'role_id', 'company_id', 'is_active'];
            $updateFields = [];
            $params = [':id' => $userId];
            
            foreach ($userData as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            if (empty($updateFields)) {
                return false;
            }
            
            // Handle password update separately
            if (isset($userData['password']) && !empty($userData['password'])) {
                $updateFields[] = "password_hash = :password_hash";
                $params[':password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
            
            return $db->execute($sql, $params) > 0;
            
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user (soft delete)
     * @param int $userId
     * @return bool
     */
    public static function delete($userId) {
        try {
            $db = DB::getInstance();
            
            return $db->execute(
                "UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = :id",
                [':id' => $userId]
            ) > 0;
            
        } catch (Exception $e) {
            error_log("User delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users by role
     * @param string $role
     * @param int $companyId
     * @return array
     */
    public static function getByRole($role, $companyId = null) {
        try {
            $db = DB::getInstance();
            
            $sql = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.username,
                    r.name as role,
                    u.company_id,
                    u.is_active,
                    u.created_at,
                    c.name as company_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE r.name = :role
                AND u.is_active = 1
            ";
            
            $params = [':role' => $role];
            
            if ($companyId !== null) {
                $sql .= " AND u.company_id = :company_id";
                $params[':company_id'] = $companyId;
            }
            
            $sql .= " ORDER BY u.name";
            
            return $db->query($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate username from email
     * @param string $email
     * @return string
     */
    private static function generateUsername($email) {
        $username = strtolower(explode('@', $email)[0]);
        $username = preg_replace('/[^a-z0-9_]/', '', $username);
        
        // Ensure uniqueness
        $db = DB::getInstance();
        $counter = 1;
        $originalUsername = $username;
        
        while (true) {
            $existing = $db->queryOne(
                "SELECT id FROM users WHERE username = :username",
                [':username' => $username]
            );
            
            if (!$existing) {
                break;
            }
            
            $username = $originalUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Validate user session
     * @param array $session
     * @return bool
     */
    public static function validateSession($session) {
        if (!isset($session['user_id']) || !isset($session['login_time'])) {
            return false;
        }
        
        // Check session timeout (24 hours)
        $sessionTimeout = 24 * 60 * 60; // 24 hours in seconds
        if (time() - $session['login_time'] > $sessionTimeout) {
            return false;
        }
        
        // Verify user still exists and is active
        $user = self::getById($session['user_id']);
        return $user && $user['is_active'];
    }
    
    /**
     * Get user statistics
     * @return array
     */
    public static function getStatistics() {
        try {
            $db = DB::getInstance();
            
            $stats = [];
            
            // Total users
            $stats['total_users'] = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
            
            // Users by role
            $roleStats = $db->query("
                SELECT r.name as role, COUNT(*) as count
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.is_active = 1
                GROUP BY r.name
            ");
            
            foreach ($roleStats as $stat) {
                $stats['by_role'][$stat['role']] = $stat['count'];
            }
            
            // Recent registrations (last 30 days)
            $stats['recent_registrations'] = $db->queryOne("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND is_active = 1
            ")['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get user statistics error: " . $e->getMessage());
            return [];
        }
    }
}
?>
