<?php
require_once 'config.php';
requireLogin();

$user_id = getUserId();
$search_term = trim($_GET['q'] ?? '');

if (empty($search_term)) {
    redirect('dashboard.php');
}

// Search for credentials
$stmt = $pdo->prepare("
    SELECT * FROM api_credentials 
    WHERE user_id = ? AND (
        api_name LIKE ? OR
        model_version LIKE ? OR
        tags LIKE ?
    )
    ORDER BY created_at DESC
");

$search_param = "%$search_term%";
$stmt->execute([$user_id, $search_param, $search_param, $search_param]);
$credentials = $stmt->fetchAll();

include 'header.php';
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Search Results: "<?php echo sanitizeInput($search_term); ?>"</h1>
        <a href="add_credential.php" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New
        </a>
    </div>
    
    <?php if (empty($credentials)): ?>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
            <div class="text-gray-500 mb-4">
                <i class="fas fa-search text-4xl"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">No results found</h2>
            <p class="text-gray-600 mb-4">Try searching with different keywords.</p>
            <a href="dashboard.php" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md">
                Back to Dashboard
            </a>
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($credentials as $credential): ?>
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-4 border-b bg-gray-50">
                        <h3 class="font-bold text-lg"><?php echo sanitizeInput($credential['api_name']); ?></h3>
                        <div class="text-sm text-gray-600">
                            <?php if (!empty($credential['model_version'])): ?>
                                <span class="mr-2">Model: <?php echo sanitizeInput($credential['model_version']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($credential['expiry_date'])): ?>
                                <span>Expires: <?php echo formatDate($credential['expiry_date']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700">API Key</h4>
                            <div x-data="{ show: false }" class="mt-1 flex items-center">
                                <span x-show="!show" class="flex-1 font-mono text-sm">••••••••••••••••••••••••••</span>
                                <span x-show="show" class="flex-1 font-mono text-sm"><?php echo decrypt($credential['api_key'], $encryption_key); ?></span>
                                <button @click="show = !show" class="text-gray-500 hover:text-gray-700 ml-2">
                                    <i x-show="!show" class="fas fa-eye"></i>
                                    <i x-show="show" class="fas fa-eye-slash"></i>
                                </button>
                                <button @click="navigator.clipboard.writeText('<?php echo decrypt($credential['api_key'], $encryption_key); ?>')" class="text-gray-500 hover:text-gray-700 ml-2">
                                    <i class="fas fa-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        
                        <?php if (!empty($credential['tags'])): ?>
                            <div>
                                <h4 class="text-sm font-medium text-gray-700">Tags</h4>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $credential['tags']) as $tag): ?>
                                        <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                                            <?php echo sanitizeInput(trim($tag)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 border-t">
                        <a href="view_credential.php?id=<?php echo $credential['id']; ?>" class="w-full block text-center border border-primary-600 text-primary-600 hover:bg-primary-50 px-4 py-2 rounded-md">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>