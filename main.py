from flask import Flask, render_template, redirect, url_for, request, session, flash, jsonify
import os
from werkzeug.security import generate_password_hash, check_password_hash
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(level=logging.DEBUG)

# Create Flask app
app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "development-secret-key")

# Configure the database
# Το URI της βάσης δεδομένων
app.config["SQLALCHEMY_DATABASE_URI"] = os.environ.get("DATABASE_URL", "sqlite:///streamify.db")
app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False

# Import db and models
from models import db, User, Playlist, Video, Follower

# Initialize the database with the app
db.init_app(app)

# Initialize the database
with app.app_context():
    db.create_all()
    
    # Create a default user if none exists
    if User.query.count() == 0:
        default_user = User(
            username="admin",
            email="admin@example.com",
            password_hash=generate_password_hash("password"),
            first_name="Admin",
            last_name="User"
        )
        db.session.add(default_user)
        db.session.commit()
    
    # Add a context processor to make current time available in templates
    @app.context_processor
    def inject_now():
        return {'now': datetime.utcnow()}

# Routes
@app.route('/')
def index():
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form.get('username')
        email = request.form.get('email')
        password = request.form.get('password')
        first_name = request.form.get('first_name')
        last_name = request.form.get('last_name')
        
        # Check if username or email already exists
        existing_user = User.query.filter((User.username == username) | (User.email == email)).first()
        if existing_user:
            flash('Username or email already exists')
            return render_template('register.html')
        
        # Create new user
        new_user = User(
            username=username,
            email=email,
            password_hash=generate_password_hash(password),
            first_name=first_name,
            last_name=last_name
        )
        
        db.session.add(new_user)
        db.session.commit()
        
        flash('Registration successful! Please log in.')
        return redirect(url_for('login'))
    
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        user = User.query.filter_by(username=username).first()
        
        if user and check_password_hash(user.password_hash, password):
            session['user_id'] = user.id
            
            # Redirect to requested page or home page
            redirect_url = session.pop('redirect_after_login', 'index')
            return redirect(url_for(redirect_url))
        
        flash('Invalid username or password')
        
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.pop('user_id', None)
    return redirect(url_for('index'))

@app.route('/profile')
@app.route('/profile/<int:user_id>')
def profile(user_id=None):
    if user_id is None and 'user_id' in session:
        user_id = session['user_id']
    elif user_id is None:
        return redirect(url_for('login'))
    
    user = User.query.get_or_404(user_id)
    is_current_user = 'user_id' in session and session['user_id'] == user_id
    
    # Check if current user is following this profile
    is_following = False
    if 'user_id' in session and session['user_id'] != user_id:
        following = Follower.query.filter_by(
            follower_id=session['user_id'], 
            following_id=user_id
        ).first()
        is_following = following is not None
    
    # Get user's playlists
    show_private = is_current_user
    if show_private:
        playlists = Playlist.query.filter_by(user_id=user_id).all()
    else:
        playlists = Playlist.query.filter_by(user_id=user_id, is_public=True).all()
    
    # Get users that this user is following
    following = db.session.query(User).join(
        Follower, User.id == Follower.following_id
    ).filter(Follower.follower_id == user_id).all()
    
    # Get users following this user
    followers = db.session.query(User).join(
        Follower, User.id == Follower.follower_id
    ).filter(Follower.following_id == user_id).all()
    
    return render_template(
        'profile.html', 
        user=user, 
        is_current_user=is_current_user,
        is_following=is_following,
        playlists=playlists,
        following=following,
        followers=followers
    )

@app.route('/follow/<int:user_id>', methods=['POST'])
def follow_user(user_id):
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Please log in to follow users')
        return redirect(url_for('login'))
    
    current_user_id = session['user_id']
    
    # Check if user exists
    target_user = User.query.get_or_404(user_id)
    
    # Can't follow yourself
    if current_user_id == user_id:
        flash('You cannot follow yourself')
        return redirect(url_for('profile', user_id=user_id))
    
    action = request.form.get('action')
    
    if action == 'follow':
        # Check if already following
        existing = Follower.query.filter_by(
            follower_id=current_user_id,
            following_id=user_id
        ).first()
        
        if not existing:
            new_follower = Follower(
                follower_id=current_user_id,
                following_id=user_id
            )
            db.session.add(new_follower)
            db.session.commit()
            flash(f'You are now following {target_user.username}')
        else:
            flash(f'You are already following {target_user.username}')
    
    elif action == 'unfollow':
        existing = Follower.query.filter_by(
            follower_id=current_user_id,
            following_id=user_id
        ).first()
        
        if existing:
            db.session.delete(existing)
            db.session.commit()
            flash(f'You have unfollowed {target_user.username}')
        else:
            flash(f'You are not following {target_user.username}')
    
    return redirect(url_for('profile', user_id=user_id))

@app.route('/playlists')
def playlists():
    page = request.args.get('page', 1, type=int)
    per_page = 12
    
    # Get public playlists with pagination
    playlists = Playlist.query.filter_by(is_public=True).order_by(
        Playlist.created_at.desc()
    ).paginate(page=page, per_page=per_page)
    
    return render_template('playlists.html', playlists=playlists)

@app.route('/create_playlist', methods=['GET', 'POST'])
def create_playlist():
    # Check if user is logged in
    if 'user_id' not in session:
        session['redirect_after_login'] = 'create_playlist'
        flash('Please log in to create a playlist')
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        title = request.form.get('title')
        is_public = 'is_public' in request.form
        user_id = session['user_id']
        
        # Validate input
        if not title:
            flash('Playlist title is required')
            return render_template('create_playlist.html')
        
        # Create new playlist
        new_playlist = Playlist(
            title=title,
            is_public=is_public,
            user_id=user_id
        )
        
        db.session.add(new_playlist)
        db.session.commit()
        
        flash('Playlist created successfully!')
        return redirect(url_for('view_playlist', playlist_id=new_playlist.id))
    
    return render_template('create_playlist.html')

@app.route('/playlist/<int:playlist_id>')
def view_playlist(playlist_id):
    playlist = Playlist.query.get_or_404(playlist_id)
    
    # Check if user can access this playlist
    can_access = True
    can_edit = False
    
    if 'user_id' in session:
        user_id = session['user_id']
        can_edit = (user_id == playlist.user_id)
        
        # Non-public playlists can only be accessed by owner or followers
        if not playlist.is_public and user_id != playlist.user_id:
            following = Follower.query.filter_by(
                follower_id=user_id, 
                following_id=playlist.user_id
            ).first()
            can_access = following is not None
    else:
        # Non-authenticated users can only access public playlists
        can_access = playlist.is_public
    
    if not can_access:
        flash('You do not have permission to view this playlist')
        return redirect(url_for('playlists'))
    
    # Get videos in the playlist
    videos = Video.query.filter_by(playlist_id=playlist_id).order_by(Video.added_at).all()
    
    # Get video to play from URL or use first one
    video_id = request.args.get('video')
    current_video = None
    
    if video_id:
        current_video = Video.query.filter_by(
            playlist_id=playlist_id, 
            youtube_id=video_id
        ).first()
    
    if not current_video and videos:
        current_video = videos[0]
    
    return render_template(
        'playlist_view.html', 
        playlist=playlist,
        videos=videos,
        current_video=current_video,
        can_edit=can_edit
    )

@app.route('/playlist/<int:playlist_id>/edit', methods=['GET', 'POST'])
def edit_playlist(playlist_id):
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Please log in to edit playlists')
        return redirect(url_for('login'))
    
    # Get playlist
    playlist = Playlist.query.get_or_404(playlist_id)
    
    # Check if user owns the playlist
    if playlist.user_id != session['user_id']:
        flash('You do not have permission to edit this playlist')
        return redirect(url_for('view_playlist', playlist_id=playlist_id))
    
    # Get videos in the playlist
    videos = Video.query.filter_by(playlist_id=playlist_id).order_by(Video.added_at).all()
    
    if request.method == 'POST':
        action = request.form.get('action')
        
        if action == 'update_details':
            # Update playlist details
            title = request.form.get('title')
            is_public = 'is_public' in request.form
            
            if not title:
                flash('Playlist title is required')
            else:
                playlist.title = title
                playlist.is_public = is_public
                db.session.commit()
                flash('Playlist updated successfully')
                
        elif action == 'remove_video' and request.form.get('video_id'):
            # Remove video from playlist
            video_id = int(request.form.get('video_id'))
            video = Video.query.get(video_id)
            
            if video and video.playlist_id == playlist_id:
                db.session.delete(video)
                db.session.commit()
                flash('Video removed from playlist')
                
        elif action == 'delete_playlist':
            # Delete the entire playlist
            db.session.delete(playlist)
            db.session.commit()
            flash('Playlist deleted successfully')
            return redirect(url_for('profile'))
    
    return render_template(
        'edit_playlist.html',
        playlist=playlist,
        videos=videos
    )

@app.route('/playlist/<int:playlist_id>/add_video', methods=['GET', 'POST'])
def add_video(playlist_id):
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Please log in to add videos')
        return redirect(url_for('login'))
    
    # Get playlist
    playlist = Playlist.query.get_or_404(playlist_id)
    
    # Check if user owns the playlist
    if playlist.user_id != session['user_id']:
        flash('You do not have permission to edit this playlist')
        return redirect(url_for('view_playlist', playlist_id=playlist_id))
    
    if request.method == 'POST':
        youtube_id = request.form.get('youtube_id')
        title = request.form.get('title')
        
        if youtube_id and title:
            # Check if video already exists in playlist
            existing = Video.query.filter_by(
                playlist_id=playlist_id,
                youtube_id=youtube_id
            ).first()
            
            if not existing:
                new_video = Video(
                    playlist_id=playlist_id,
                    youtube_id=youtube_id,
                    title=title
                )
                db.session.add(new_video)
                db.session.commit()
                flash('Video added to playlist')
            else:
                flash('This video is already in the playlist')
                
        return redirect(url_for('add_video', playlist_id=playlist_id))
    
    # Get search query
    query = request.args.get('q', '')
    search_results = []
    
    # Perform search if query provided
    if query:
        # In a real app, this would call the YouTube API
        # For now, use our mock API endpoint
        search_response = search_youtube()
        search_results = search_response.json['items']
    
    return render_template(
        'add_video.html',
        playlist=playlist,
        query=query,
        search_results=search_results
    )

@app.route('/api/youtube/search')
def search_youtube():
    query = request.args.get('q', '')
    # This would normally call the YouTube API
    # For now, return a mock response for testing
    if not query:
        return jsonify({'items': []})
    
    # Mock YouTube search results
    results = {
        'items': [
            {
                'id': {'videoId': 'dQw4w9WgXcQ'},
                'snippet': {
                    'title': 'Test Video 1',
                    'thumbnails': {'medium': {'url': 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg'}},
                    'channelTitle': 'Test Channel'
                }
            },
            {
                'id': {'videoId': 'xvFZjo5PgG0'},
                'snippet': {
                    'title': 'Test Video 2',
                    'thumbnails': {'medium': {'url': 'https://img.youtube.com/vi/xvFZjo5PgG0/mqdefault.jpg'}},
                    'channelTitle': 'Another Channel'
                }
            }
        ]
    }
    
    return jsonify(results)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)