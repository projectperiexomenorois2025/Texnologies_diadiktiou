<?php
// Include header
require_once 'includes/header.php';

// Get page number for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12; // Number of playlists per page

// Get total count of public playlists
$count_sql = "SELECT COUNT(*) AS total FROM playlists WHERE is_public = 1";
$count_result = $conn->query($count_sql);
$total_playlists = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_playlists / $per_page);

// Ensure page is within valid range
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $per_page;

// Get public playlists with pagination
$sql = "SELECT p.*, u.username, COUNT(v.video_id) as video_count 
        FROM playlists p 
        JOIN users u ON p.user_id = u.user_id 
        LEFT JOIN videos v ON p.playlist_id = v.playlist_id 
        WHERE p.is_public = 1 
        GROUP BY p.playlist_id 
        ORDER BY p.created_at DESC 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $per_page);
$stmt->execute();
$result = $stmt->get_result();

$playlists = [];
while ($row = $result->fetch_assoc()) {
    $playlists[] = $row;
}
?>

<section class="playlists-section">
    <div class="section-header">
        <h1>Public Playlists</h1>
        <?php if (isAuthenticated()): ?>
            <a href="create_playlist.php" class="btn">Create New Playlist</a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($playlists)): ?>
        <div class="alert alert-info">No playlists available yet.</div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($playlists as $playlist): ?>
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($playlist['title']); ?></h3>
                        <p class="card-meta">
                            By <a href="profile.php?id=<?php echo $playlist['user_id']; ?>"><?php echo htmlspecialchars($playlist['username']); ?></a> • 
                            <?php echo htmlspecialchars($playlist['video_count']); ?> videos • 
                            Created: <?php echo date('M j, Y', strtotime($playlist['created_at'])); ?>
                        </p>
                        <a href="playlist_view.php?id=<?php echo $playlist['playlist_id']; ?>" class="btn">View Playlist</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="playlists.php?page=<?php echo $page - 1; ?>" class="pagination-link">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="playlists.php?page=<?php echo $i; ?>" class="pagination-link <?php echo ($i == $page) ? 'pagination-current' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="playlists.php?page=<?php echo $page + 1; ?>" class="pagination-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
