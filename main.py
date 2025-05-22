from flask import Flask, render_template, redirect, url_for, request, session, flash, jsonify
import os
from werkzeug.security import generate_password_hash, check_password_hash
import logging
from datetime import datetime
from google.oauth2.credentials import Credentials
import google_auth_oauthlib.flow
import json

# Configure logging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s %(levelname)s: %(message)s [in %(pathname)s:%(lineno)d]'
)

# Create Flask app
app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "development-secret-key")

# Error handling
@app.errorhandler(500)
def internal_error(error):
    db.session.rollback()
    app.logger.error(f'Server Error: {error}')
    if app.debug:
        # In debug mode, show detailed error
        return render_template('error.html', error=str(error), debug_info=error.__dict__), 500
    else:
        # In production, show generic error
        return render_template('error.html', error="Σφάλμα διακομιστή. Παρακαλώ προσπαθήστε ξανά αργότερα."), 500

@app.errorhandler(404)
def not_found_error(error):
    return render_template('error.html', error=error), 404

@app.errorhandler(400)
def bad_request_error(error):
    app.logger.error(f'Bad Request Error: {error}')
    return render_template('error.html', error="Η αίτηση δεν μπορεί να επεξεργαστεί. Παρακαλώ ελέγξτε τα δεδομένα σας και προσπαθήστε ξανά."), 400

# Configure the database
# Το URI της βάσης δεδομένων
database_url = os.environ.get("DATABASE_URL", "sqlite:///streamify.db")
if database_url.startswith("postgres://"):
    database_url = database_url.replace("postgres://", "postgresql://", 1)
app.config["SQLALCHEMY_DATABASE_URI"] = database_url
app.config["SQLALCHEMY_ENGINE_OPTIONS"] = {
    "pool_pre_ping": True,
    "pool_recycle": 300,
}
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

@app.route('/about')
def about():
    return render_template('about.html')

@app.route('/help')
def help():
    return render_template('help.html')

@app.route('/policies')
def policies():
    return render_template('policies.html')

@app.route('/search')
def search():
    query = request.args.get('text_search', '')
    date_from = request.args.get('date_from')
    date_to = request.args.get('date_to')
    user_search = request.args.get('user_search', '')
    page = request.args.get('page', 1, type=int)
    per_page = request.args.get('per_page', 10, type=int)

    # Base query
    query_obj = Playlist.query

    # Add search conditions
    if query:
        query_obj = query_obj.filter(
            db.or_(
                Playlist.title.ilike(f'%{query}%'),
                Playlist.videos.any(Video.title.ilike(f'%{query}%'))
            )
        )
    
    # Date range filter
    if date_from:
        query_obj = query_obj.filter(Playlist.created_at >= date_from)
    if date_to:
        query_obj = query_obj.filter(Playlist.created_at <= date_to)

    # User search
    if user_search:
        query_obj = query_obj.join(User).filter(
            db.or_(
                User.username.ilike(f'%{user_search}%'),
                User.first_name.ilike(f'%{user_search}%'),
                User.last_name.ilike(f'%{user_search}%'),
                User.email.ilike(f'%{user_search}%')
            )
        )

    # Show only public playlists or user's own playlists
    if 'user_id' in session:
        query_obj = query_obj.filter(
            db.or_(
                Playlist.is_public == True,
                Playlist.user_id == session['user_id']
            )
        )
    else:
        query_obj = query_obj.filter(Playlist.is_public == True)

    # Order by latest first and paginate
    query_obj = query_obj.order_by(Playlist.created_at.desc())
    results = query_obj.paginate(page=page, per_page=per_page, error_out=False)

    # Get total number of results
    total_results = query_obj.count()

    return render_template('search.html', 
                         results=results,
                         total_results=total_results,
                         query=query,
                         date_from=date_from,
                         date_to=date_to,
                         user_search=user_search)

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

@app.route('/edit_profile', methods=['GET', 'POST'])
def edit_profile():
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να επεξεργαστείτε το προφίλ σας')
        return redirect(url_for('login'))

    user_id = session['user_id']
    user = User.query.get_or_404(user_id)

    if request.method == 'POST':
        action = request.form.get('action')

        if action == 'update_profile':
            # Update user profile
            username = request.form.get('username')
            email = request.form.get('email')
            first_name = request.form.get('first_name')
            last_name = request.form.get('last_name')
            current_password = request.form.get('current_password')
            new_password = request.form.get('new_password')
            confirm_password = request.form.get('confirm_password')

            # Validate input
            if not username or not email or not first_name or not last_name:
                flash('Όλα τα πεδία είναι υποχρεωτικά')
                return render_template('edit_profile.html', user=user)

            # Check if username or email is already taken by another user
            existing_user = User.query.filter(
                (User.username == username) | (User.email == email),
                User.id != user_id
            ).first()

            if existing_user:
                if existing_user.username == username:
                    flash('Το όνομα χρήστη χρησιμοποιείται ήδη')
                else:
                    flash('Το email χρησιμοποιείται ήδη')
                return render_template('edit_profile.html', user=user)

            # Update user details
            user.username = username
            user.email = email
            user.first_name = first_name
            user.last_name = last_name

            # Update password if provided
            if new_password:
                # Verify current password
                if not check_password_hash(user.password_hash, current_password):
                    flash('Ο τρέχων κωδικός πρόσβασης είναι λανθασμένος')
                    return render_template('edit_profile.html', user=user)

                # Verify new password and confirmation match
                if new_password != confirm_password:
                    flash('Οι νέοι κωδικοί πρόσβασης δεν ταιριάζουν')
                    return render_template('edit_profile.html', user=user)

                # Update password
                user.password_hash = generate_password_hash(new_password)

            db.session.commit()
            flash('Το προφίλ σας ενημερώθηκε με επιτυχία')
            return redirect(url_for('profile'))

        elif action == 'delete_account':
            # Verify password before deleting account
            password = request.form.get('password')

            if not check_password_hash(user.password_hash, password):
                flash('Ο κωδικός πρόσβασης είναι λανθασμένος')
                return render_template('edit_profile.html', user=user)

            # Delete all user data

            # 1. Delete all videos from user's playlists
            user_playlists = Playlist.query.filter_by(user_id=user_id).all()
            for playlist in user_playlists:
                Video.query.filter_by(playlist_id=playlist.id).delete()

            # 2. Delete all playlists
            Playlist.query.filter_by(user_id=user_id).delete()

            # 3. Delete all following relationships
            Follower.query.filter(
                (Follower.follower_id == user_id) | (Follower.following_id == user_id)
            ).delete()

            # 4. Delete the user
            db.session.delete(user)
            db.session.commit()

            # Clear session
            session.clear()

            flash('Ο λογαριασμός σας έχει διαγραφεί με επιτυχία')
            return redirect(url_for('index'))

    return render_template('edit_profile.html', user=user)

@app.route('/following')
def following():
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να δείτε τους ακολουθούμενους χρήστες')
        return redirect(url_for('login'))
    
    user = User.query.get_or_404(session['user_id'])
    following = db.session.query(User).join(Follower, User.id == Follower.following_id)\
        .filter(Follower.follower_id == user.id).all()
    followers = db.session.query(User).join(Follower, User.id == Follower.follower_id)\
        .filter(Follower.following_id == user.id).all()
    
    return render_template('following.html', user=user, following=following, followers=followers)

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

@app.route('/export_playlists')
def export_playlists():
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να εξάγετε τις λίστες')
        return redirect(url_for('login'))
    
    playlists = Playlist.query.filter(
        (Playlist.is_public == True) | (Playlist.user_id == session['user_id'])
    ).all()
    
    # Create YAML data
    data = {
        'playlists': [{
            'id': p.id,
            'title': p.title,
            'is_public': p.is_public,
            'created_at': p.created_at.isoformat(),
            'videos': [{
                'youtube_id': v.youtube_id,
                'title': v.title
            } for v in p.videos]
        } for p in playlists]
    }
    
    return jsonify(data)

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
        flash('Παρακαλώ συνδεθείτε για να προσθέσετε βίντεο')
        return redirect(url_for('login'))

    # Get playlist
    playlist = Playlist.query.get_or_404(playlist_id)

    # Check if user owns the playlist
    if playlist.user_id != session['user_id']:
        flash('Δεν έχετε δικαίωμα επεξεργασίας αυτής της λίστας')
        return redirect(url_for('view_playlist', playlist_id=playlist_id))

    # Store the current playlist_id in session for the OAuth callback
    session['current_playlist_id'] = playlist_id

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
                flash('Το βίντεο προστέθηκε στη λίστα')
            else:
                flash('Αυτό το βίντεο υπάρχει ήδη στη λίστα')

        return redirect(url_for('add_video', playlist_id=playlist_id))

    # Get search query
    query = request.args.get('q', '')
    search_results = []

    # Check if we have YouTube credentials
    has_youtube_auth = 'youtube_credentials' in session

    # Perform search if query provided and authenticated
    if query and has_youtube_auth:
        try:
            # Import YouTube API modules
            from youtube_api import get_youtube_client, search_videos
            from google.oauth2.credentials import Credentials

            # Create credentials from stored session data
            credentials = Credentials(
                token=session['youtube_credentials']['token'],
                refresh_token=session['youtube_credentials']['refresh_token'],
                token_uri=session['youtube_credentials']['token_uri'],
                client_id=session['youtube_credentials']['client_id'],
                client_secret=session['youtube_credentials']['client_secret'],
                scopes=session['youtube_credentials']['scopes']
            )

            # Build YouTube client
            youtube = get_youtube_client(credentials)

            # Search videos
            response = search_videos(youtube, query)

            # Extract search results
            if 'items' in response:
                search_results = response['items']
        except Exception as e:
            app.logger.error(f"YouTube API Error: {str(e)}")
            flash(f'Σφάλμα κατά την αναζήτηση: {str(e)}')
            # If token expired, clear credentials
            session.pop('youtube_credentials', None)
            has_youtube_auth = False

    return render_template(
        'add_video.html',
        playlist=playlist,
        query=query,
        search_results=search_results,
        has_youtube_auth=has_youtube_auth,
        auth_url=url_for('youtube_auth') if not has_youtube_auth else None
    )

# YouTube OAuth routes
@app.route('/youtube/auth')
def youtube_auth():
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να χρησιμοποιήσετε την αναζήτηση YouTube')
        return redirect(url_for('login'))

    # Import YouTube API module
    from youtube_api import get_oauth_flow

    # Create flow instance
    flow = get_oauth_flow()

    # Generate authorization URL
    authorization_url, state = flow.authorization_url(
        access_type='offline',
        include_granted_scopes='true'
    )

    # Store the state in the session for later validation
    session['youtube_oauth_state'] = state

    # Redirect to Google authorization page
    return redirect(authorization_url)

@app.route('/youtube/callback')
def youtube_oauth_callback():
    # Check for authorization errors
    if 'error' in request.args:
        flash(f'Σφάλμα άδειας: {request.args.get("error")}')
        return redirect(url_for('add_video', playlist_id=session.get('current_playlist_id', 0)))

    # Ensure state parameter matches to prevent CSRF
    if 'youtube_oauth_state' not in session:
        flash('Μη έγκυρη κατάσταση επαλήθευσης. Προσπαθήστε ξανά.')
        return redirect(url_for('index'))

    # Import YouTube API module
    from youtube_api import get_oauth_flow

    # Create flow instance
    flow = get_oauth_flow()

    # Complete the OAuth flow and obtain credentials
    flow.fetch_token(
        authorization_response=request.url,
        state=session['youtube_oauth_state']
    )

    # Get credentials and store in session
    credentials = flow.credentials
    session['youtube_credentials'] = {
        'token': credentials.token,
        'refresh_token': credentials.refresh_token,
        'token_uri': credentials.token_uri,
        'client_id': credentials.client_id,
        'client_secret': credentials.client_secret,
        'scopes': credentials.scopes
    }

    # Clear state
    session.pop('youtube_oauth_state', None)

    # Redirect to the video search page
    playlist_id = session.get('current_playlist_id', 0)
    if playlist_id:
        return redirect(url_for('add_video', playlist_id=playlist_id))
    else:
        flash('Επιτυχής σύνδεση με το YouTube API')
        return redirect(url_for('playlists'))

@app.route('/api/youtube/search')
def search_youtube():
    query = request.args.get('q', '')

    if not query:
        return jsonify({'items': []})

    # Check if we have YouTube credentials
    if 'youtube_credentials' not in session:
        return jsonify({
            'error': 'authentication_required',
            'message': 'Απαιτείται σύνδεση με το λογαριασμό YouTube',
            'auth_url': url_for('youtube_auth')
        })

    try:
        # Import YouTube API modules
        from youtube_api import get_youtube_client, search_videos
        from google.oauth2.credentials import Credentials

        # Create credentials from stored session data
        credentials = Credentials(
            token=session['youtube_credentials']['token'],
            refresh_token=session['youtube_credentials']['refresh_token'],
            token_uri=session['youtube_credentials']['token_uri'],
            client_id=session['youtube_credentials']['client_id'],
            client_secret=session['youtube_credentials']['client_secret'],
            scopes=session['youtube_credentials']['scopes']
        )

        # Build YouTube client
        youtube = get_youtube_client(credentials)

        # Search videos
        results = search_videos(youtube, query)

        return jsonify(results)

    except Exception as e:
        app.logger.error(f"YouTube API Error: {str(e)}")
        # If token expired, clear credentials and require re-authentication
        session.pop('youtube_credentials', None)
        return jsonify({
            'error': 'api_error',
            'message': f'Σφάλμα κατά την αναζήτηση: {str(e)}',
            'auth_url': url_for('youtube_auth')
        })

# Export routes
@app.route('/export')
def export_page():
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να εξάγετε τα δεδομένα σας')
        return redirect(url_for('login'))
    
    user_id = session['user_id']
    user = User.query.get_or_404(user_id)
    
    # Get user's playlists
    playlists = Playlist.query.filter_by(user_id=user_id).all()
    
    return render_template('export.html', user=user, playlists=playlists)

@app.route('/export/playlist/<int:playlist_id>')
def export_playlist(playlist_id):
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να εξάγετε τα δεδομένα σας')
        return redirect(url_for('login'))
    
    # Get playlist
    playlist = Playlist.query.get_or_404(playlist_id)
    
    # Check if user owns the playlist or if it's public
    if playlist.user_id != session['user_id'] and not playlist.is_public:
        flash('Δεν έχετε δικαίωμα εξαγωγής αυτής της λίστας')
        return redirect(url_for('playlists'))
    
    # Get playlist videos
    videos = Video.query.filter_by(playlist_id=playlist_id).all()
    
    # Create JSON export
    creator = User.query.get(playlist.user_id)
    export_data = {
        'playlist': {
            'title': playlist.title,
            'creator': creator.username,
            'is_public': playlist.is_public,
            'created_at': playlist.created_at.strftime('%Y-%m-%d %H:%M:%S'),
            'videos': []
        }
    }
    
    for video in videos:
        export_data['playlist']['videos'].append({
            'youtube_id': video.youtube_id,
            'title': video.title,
            'added_at': video.added_at.strftime('%Y-%m-%d %H:%M:%S')
        })
    
    # Return JSON response
    response = jsonify(export_data)
    response.headers['Content-Disposition'] = f'attachment; filename=playlist_{playlist_id}.json'
    return response

@app.route('/export/all')
def export_all_playlists():
    # Check if user is logged in
    if 'user_id' not in session:
        flash('Παρακαλώ συνδεθείτε για να εξάγετε τα δεδομένα σας')
        return redirect(url_for('login'))
    
    user_id = session['user_id']
    user = User.query.get_or_404(user_id)
    
    # Get all user's playlists
    playlists = Playlist.query.filter_by(user_id=user_id).all()
    
    # Create JSON export
    export_data = {
        'user': {
            'username': user.username,
            'email': user.email,
            'created_at': user.created_at.strftime('%Y-%m-%d %H:%M:%S'),
            'playlists': []
        }
    }
    
    for playlist in playlists:
        playlist_data = {
            'id': playlist.id,
            'title': playlist.title,
            'is_public': playlist.is_public,
            'created_at': playlist.created_at.strftime('%Y-%m-%d %H:%M:%S'),
            'videos': []
        }
        
        # Get videos for this playlist
        videos = Video.query.filter_by(playlist_id=playlist.id).all()
        
        for video in videos:
            playlist_data['videos'].append({
                'youtube_id': video.youtube_id,
                'title': video.title,
                'added_at': video.added_at.strftime('%Y-%m-%d %H:%M:%S')
            })
        
        export_data['user']['playlists'].append(playlist_data)
    
    # Return JSON response
    response = jsonify(export_data)
    response.headers['Content-Disposition'] = f'attachment; filename={user.username}_playlists.json'
    return response

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)