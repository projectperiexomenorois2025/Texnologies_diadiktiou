<?php
// Include header
require_once 'includes/header.php';
?>

<section class="help-section">
    <h1>Help & Support</h1>
    
    <div class="accordion-container">
        <div class="accordion">
            <div class="accordion-header">
                Getting Started
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Creating an Account</h3>
                <p>To create an account on Streamify:</p>
                <ol>
                    <li>Click the "Register" button in the top navigation bar</li>
                    <li>Fill in your personal details (first name, last name, username, email, and password)</li>
                    <li>Click "Register" to create your account</li>
                    <li>You'll be automatically logged in and can start using the platform</li>
                </ol>
                
                <h3>Logging In</h3>
                <p>To log in to an existing account:</p>
                <ol>
                    <li>Click the "Login" button in the top navigation bar</li>
                    <li>Enter your username and password</li>
                    <li>Click "Login" to access your account</li>
                </ol>
                
                <h3>Creating Your First Playlist</h3>
                <p>After logging in, you can create your first playlist:</p>
                <ol>
                    <li>Click "Create Playlist" in the navigation menu</li>
                    <li>Enter a title for your playlist</li>
                    <li>Choose whether the playlist should be public or private</li>
                    <li>Click "Create Playlist" to save it</li>
                    <li>You'll be redirected to add videos to your new playlist</li>
                </ol>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Managing Your Profile
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Viewing Your Profile</h3>
                <p>To view your profile:</p>
                <ol>
                    <li>Click on your username in the top navigation bar</li>
                    <li>Select "My Profile" from the dropdown menu</li>
                </ol>
                <p>Your profile shows your personal information, playlists, and users you follow.</p>
                
                <h3>Editing Profile Information</h3>
                <p>To edit your profile information:</p>
                <ol>
                    <li>Click on your username in the top navigation bar</li>
                    <li>Select "Edit Profile" from the dropdown menu</li>
                    <li>Update your first name, last name, email, or password</li>
                    <li>Enter your current password to confirm changes</li>
                    <li>Click "Update Profile" to save changes</li>
                </ol>
                
                <h3>Deleting Your Account</h3>
                <p>If you wish to delete your account:</p>
                <ol>
                    <li>Go to "Edit Profile"</li>
                    <li>Scroll to the bottom of the page</li>
                    <li>Click "Delete Account"</li>
                    <li>Enter your password to confirm</li>
                    <li>Click "Delete Account" to permanently remove your account and all your playlists</li>
                </ol>
                <p><strong>Warning:</strong> This action cannot be undone. All your playlists will be permanently deleted.</p>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Working with Playlists
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Adding Videos to a Playlist</h3>
                <p>To add videos to your playlist:</p>
                <ol>
                    <li>Go to your playlist page</li>
                    <li>Click "Add Videos"</li>
                    <li>Search for videos using the search bar</li>
                    <li>Click "Add to Playlist" on any video you want to add</li>
                </ol>
                
                <h3>Playing a Playlist</h3>
                <p>To play a playlist:</p>
                <ol>
                    <li>Open the playlist you want to play</li>
                    <li>Click on any video to start playing from that point</li>
                    <li>Videos will play one after another automatically</li>
                </ol>
                
                <h3>Changing Playlist Privacy</h3>
                <p>To change a playlist's privacy settings:</p>
                <ol>
                    <li>Go to the playlist page</li>
                    <li>Click "Edit Playlist"</li>
                    <li>Change the privacy setting to Public or Private</li>
                    <li>Click "Save Changes"</li>
                </ol>
                <p><strong>Note:</strong> Public playlists can be seen by anyone, while private playlists are only visible to you and users who follow you.</p>
                
                <h3>Deleting a Playlist</h3>
                <p>To delete a playlist:</p>
                <ol>
                    <li>Go to the playlist page</li>
                    <li>Click "Edit Playlist"</li>
                    <li>Scroll to the bottom and click "Delete Playlist"</li>
                    <li>Confirm your decision</li>
                </ol>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Following and Social Features
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Following Other Users</h3>
                <p>To follow another user:</p>
                <ol>
                    <li>Visit their profile page by clicking on their username in a playlist</li>
                    <li>Click the "Follow" button</li>
                </ol>
                <p>Once you follow someone, their public playlists will appear in your "Following" section.</p>
                
                <h3>Viewing Who You Follow</h3>
                <p>To see users you follow:</p>
                <ol>
                    <li>Click "Following" in the main navigation</li>
                    <li>You'll see a list of users you follow and their public playlists</li>
                </ol>
                
                <h3>Unfollowing Users</h3>
                <p>To unfollow a user:</p>
                <ol>
                    <li>Visit their profile page</li>
                    <li>Click the "Unfollow" button</li>
                </ol>
                <p>Their playlists will no longer appear in your "Following" section.</p>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Searching and Discovering Content
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Searching for Playlists</h3>
                <p>To search for playlists:</p>
                <ol>
                    <li>Click "Search" in the main navigation</li>
                    <li>Enter keywords in the search field</li>
                    <li>Optionally, add date ranges or user information to refine your search</li>
                    <li>Click "Search" to see results</li>
                </ol>
                
                <h3>Searching for YouTube Videos</h3>
                <p>To search for videos to add to your playlist:</p>
                <ol>
                    <li>Go to any of your playlists and click "Add Videos"</li>
                    <li>Enter keywords in the YouTube search field</li>
                    <li>Click "Search" to see results directly from YouTube</li>
                    <li>Click "Add to Playlist" on any video you want to add</li>
                </ol>
                
                <h3>Browse Popular Playlists</h3>
                <p>To discover popular content:</p>
                <ol>
                    <li>Go to the "Playlists" page from the main navigation</li>
                    <li>Browse through recently created public playlists</li>
                </ol>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Theme Settings
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Switching Between Light and Dark Theme</h3>
                <p>Streamify offers both light and dark themes for comfortable viewing:</p>
                <ol>
                    <li>Click the sun/moon icon in the top navigation bar</li>
                    <li>The theme will instantly switch between light and dark</li>
                    <li>Your preference will be saved for future visits</li>
                </ol>
                <p>The theme setting is stored in a cookie on your device, so it will persist across browser sessions.</p>
            </div>
        </div>
        
        <div class="accordion">
            <div class="accordion-header">
                Exporting Data
                <i class="fas fa-chevron-down accordion-icon"></i>
            </div>
            <div class="accordion-content">
                <h3>Exporting Your Playlists</h3>
                <p>To export your playlists as YAML:</p>
                <ol>
                    <li>Click "Export Data" in the main navigation</li>
                    <li>Your playlists will be exported in YAML format</li>
                    <li>You can save this file for backup or sharing purposes</li>
                </ol>
                <p>The exported data includes all your playlists and their content, with personal information anonymized for privacy.</p>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
