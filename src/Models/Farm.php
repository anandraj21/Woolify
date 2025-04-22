<?php
require_once __DIR__ . '/BaseModel.php';

class Farm extends BaseModel {
    public function __construct() {
        parent::__construct('farmers'); // Note: Table name from schema is 'farmers' which links to users
        // The schema had a `farmers` table storing farm details, not a separate `farms` table.
        // We will use the existing `farmers` table which has farm_name, farm_location etc.
    }

    /**
     * Find farm details by the main user ID.
     * This assumes one primary farm record per farmer user for simplicity now.
     * If a user can have multiple farms, the schema needs adjustment.
     */
    public function findFarmByUserId(int $userId): ?array 
    {
        $results = $this->findAll(['user_id' => $userId], null, 1); // Find all with limit 1
        return !empty($results) ? $results[0] : null; // Return the first result if found
    }

    /**
     * Find farm details by the farmer ID (the ID from the farmers table itself).
     */
    public function findFarmByFarmerId(int $farmerId): ?array 
    {
         return $this->read($farmerId); // Use BaseModel's read method
    }
    
    /**
     * Create a new farm record (actually creating the farmer record with farm details).
     * This is usually handled during registration.
     * Adding a standalone "add farm" might imply a user can manage multiple farms,
     * which the current schema (one farmer record per user) doesn't directly support.
     *
     * If the intent IS multiple farms per user, the schema needs a dedicated `farms` table 
     * with a foreign key to `users` or `farmers`.
     *
     * For now, let's provide an update method instead of create.
     */
    // public function createFarm(...) { ... }

    /**
     * Update farm details for a given farmer ID.
     *
     * @param int $farmerId The ID from the farmers table.
     * @param array $data Associative array of data to update (e.g., ['farm_name' => 'New Name']).
     * @return bool True on success, false on failure.
     */
    public function updateFarmDetails(int $farmerId, array $data): bool 
    {
        // Basic validation/sanitization could be added here
        $allowedColumns = ['farm_name', 'farm_location', 'farm_size_acres', 'contact_number']; // Example
        $updateData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedColumns)) {
                $updateData[$key] = $value; // Keep allowed columns
            }
        }

        if (empty($updateData)) {
            return false; // Nothing valid to update
        }

        return $this->update($farmerId, $updateData);
    }

    // Add other farm-specific methods if needed...

}
?> 