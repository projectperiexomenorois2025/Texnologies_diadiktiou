
<?php
require_once 'includes/header.php';

// Initialize search parameters
$query = isset($_GET['query']) ? sanitize($_GET['query']) : '';
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
$user = isset($_GET['user']) ? sanitize($_GET['user']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

// Validate per_page to be either 10 or 25
$per_page = ($per_page == 25) ? 25 : 10;

// Search results
$results = null;

// If any search parameter is provided, perform search
if (!empty($query) || !empty($start_date) || !empty($end_date) || !empty($user)) {
    $results = searchPlaylists($query, $start_date, $end_date, $user, $page, $per_page);
}
?>

<div class="container">
    <h1>Αναζήτηση Λιστών</h1>
    
    <div class="search-form">
        <form method="GET" action="search.php" id="search-form">
            <div class="form-group">
                <label for="query">Αναζήτηση Κειμένου</label>
                <input type="text" class="form-control" id="query" name="query" 
                       placeholder="Αναζήτηση σε τίτλους λιστών και βίντεο" 
                       value="<?php echo htmlspecialchars($query); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="start_date">Από Ημερομηνία</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                
                <div class="form-group col-md-6">
                    <label for="end_date">Έως Ημερομηνία</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label for="user">Χρήστης</label>
                    <input type="text" class="form-control" id="user" name="user" 
                           placeholder="Όνομα, επώνυμο, username ή email" 
                           value="<?php echo htmlspecialchars($user); ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label for="per_page">Αποτελέσματα ανά σελίδα</label>
                    <select class="form-control" id="per_page" name="per_page">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Αναζήτηση</button>
                <button type="button" class="btn btn-secondary" onclick="clearForm()">Καθαρισμός</button>
            </div>
        </form>
    </div>

    <?php if ($results): ?>
        <div class="search-results">
            <h2>Αποτελέσματα Αναζήτησης</h2>
            
            <?php if (empty($results['playlists'])): ?>
                <div class="alert alert-info">Δεν βρέθηκαν λίστες που να ταιριάζουν με τα κριτήρια αναζήτησης.</div>
            <?php else: ?>
                <p>Βρέθηκαν <?php echo $results['total']; ?> λίστα(ες)</p>
                
                <div class="playlists-grid">
                    <?php foreach ($results['playlists'] as $playlist): ?>
                        <div class="playlist-card">
                            <h3><?php echo htmlspecialchars($playlist['title']); ?></h3>
                            <div class="playlist-meta">
                                <p>Από: <?php echo htmlspecialchars($playlist['username']); ?></p>
                                <p>Ημ/νία: <?php echo date('d/m/Y', strtotime($playlist['created_at'])); ?></p>
                            </div>
                            <div class="playlist-actions">
                                <a href="playlist_view.php?id=<?php echo $playlist['playlist_id']; ?>" 
                                   class="btn btn-primary">Προβολή</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($results['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($results['page'] > 1): ?>
                            <a href="<?php echo buildPaginationUrl($results['page'] - 1); ?>" 
                               class="page-link">&laquo; Προηγούμενη</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $results['page'] - 2); 
                                 $i <= min($results['total_pages'], $results['page'] + 2); $i++): ?>
                            <a href="<?php echo buildPaginationUrl($i); ?>" 
                               class="page-link <?php echo ($i == $results['page']) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($results['page'] < $results['total_pages']): ?>
                            <a href="<?php echo buildPaginationUrl($results['page'] + 1); ?>" 
                               class="page-link">Επόμενη &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function clearForm() {
    document.getElementById('query').value = '';
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('user').value = '';
    document.getElementById('per_page').value = '10';
}
</script>

<style>
.search-form {
    background: var(--bg-secondary);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.playlists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.playlist-card {
    background: var(--bg-secondary);
    padding: 15px;
    border-radius: 8px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.page-link {
    padding: 5px 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    text-decoration: none;
}

.page-link.active {
    background: var(--primary-color);
    color: white;
}
</style>

<?php
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'search.php?' . http_build_query($params);
}

require_once 'includes/footer.php';
?>
