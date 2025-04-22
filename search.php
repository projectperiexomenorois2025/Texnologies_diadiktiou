<?php
// Include header
require_once 'includes/header.php';

// Initialize variables
$query = isset($_GET['query']) ? sanitize($_GET['query']) : '';
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
$user = isset($_GET['user']) ? sanitize($_GET['user']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

// Validate per_page to be either 10 or 25
if ($per_page !== 10 && $per_page !== 25) {
    $per_page = 10;
}

// Search results
$results = null;

// If any search parameter is provided, perform search
if (!empty($query) || !empty($start_date) || !empty($end_date) || !empty($user)) {
    $results = searchPlaylists($query, $start_date, $end_date, $user, $page, $per_page);
}
?>

<section class="search-section">
    <div class="container">
        <h1>Search Playlists</h1>
        
        <div class="search-form">
            <form method="GET" action="search.php" id="search-form">
                <div class="search-row">
                    <div class="search-field">
                        <label for="query">Search Text</label>
                        <input type="text" class="form-control" id="query" name="query" 
                               placeholder="Search in playlist and video titles" 
                               value="<?php echo htmlspecialchars($query); ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="user">User</label>
                        <input type="text" class="form-control" id="user" name="user" 
                               placeholder="Search by username, name or email" 
                               value="<?php echo htmlspecialchars($user); ?>">
                    </div>
                </div>
                
                <div class="search-row">
                    <div class="search-field">
                        <label for="start_date">From Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="end_date">To Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="per_page">Results Per Page</label>
                        <select class="form-control" id="per_page" name="per_page">
                            <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                        </select>
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="btn">Search</button>
                    <button type="button" class="btn" onclick="clearForm()">Clear</button>
                </div>
            </form>
        </div>
        
        <?php if ($results): ?>
            <div class="search-results">
                <h2>Search Results</h2>
                
                <?php if (empty($results['playlists'])): ?>
                    <div class="alert alert-info">No playlists found matching your criteria.</div>
                <?php else: ?>
                    <p>Found <?php echo $results['total']; ?> playlist(s)</p>
                    
                    <div class="grid">
                        <?php foreach ($results['playlists'] as $playlist): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title">
                                        <?php echo htmlspecialchars($playlist['title']); ?>
                                        <?php if (!$playlist['is_public']): ?>
                                            <span class="private-badge"><i class="fas fa-lock"></i></span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="card-meta">
                                        By <a href="profile.php?id=<?php echo $playlist['user_id']; ?>"><?php echo htmlspecialchars($playlist['username']); ?></a> • 
                                        Created: <?php echo date('M j, Y', strtotime($playlist['created_at'])); ?>
                                    </p>
                                    <a href="playlist_view.php?id=<?php echo $playlist['playlist_id']; ?>" class="btn">View Playlist</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($results['total_pages'] > 1): ?>
                        <div class="pagination">
                            <?php if ($results['page'] > 1): ?>
                                <a href="<?php echo buildPaginationUrl($results['page'] - 1); ?>" class="pagination-link">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $results['page'] - 2); $i <= min($results['total_pages'], $results['page'] + 2); $i++): ?>
                                <a href="<?php echo buildPaginationUrl($i); ?>" class="pagination-link <?php echo ($i == $results['page']) ? 'pagination-current' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($results['page'] < $results['total_pages']): ?>
                                <a href="<?php echo buildPaginationUrl($results['page'] + 1); ?>" class="pagination-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Helper function to build pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'search.php?' . http_build_query($params);
}
?>

<script>
function clearForm() {
    document.getElementById('query').value = '';
    document.getElementById('user').value = '';
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('per_page').value = '10';
}
</script>

<style>
.search-form {
    background-color: var(--search-form-bg);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
}

.search-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.search-field {
    flex: 1;
}

.search-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.search-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.private-badge {
    display: inline-flex;
    font-size: 14px;
    margin-left: 8px;
    color: var(--text-color);
    opacity: 0.7;
}

@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
