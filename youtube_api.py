
import os
import googleapiclient.discovery
import googleapiclient.errors
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import Flow
from flask import current_app, url_for

# OAuth credentials
CLIENT_ID = os.environ.get('GOOGLE_OAUTH_CLIENT_ID')
CLIENT_SECRET = os.environ.get('GOOGLE_OAUTH_CLIENT_SECRET')
API_KEY = os.environ.get('YOUTUBE_API_KEY')

# YouTube API service name and version
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

# Define scopes needed for the application
SCOPES = [
    'https://www.googleapis.com/auth/youtube.readonly',
    'https://www.googleapis.com/auth/youtube',
    'https://www.googleapis.com/auth/youtube.force-ssl'
]

def get_client_config():
    """Get the client configuration for OAuth2 flow."""
    if not CLIENT_ID or not CLIENT_SECRET:
        raise ValueError("Missing OAuth credentials")
        
    return {
        "web": {
            "client_id": CLIENT_ID,
            "client_secret": CLIENT_SECRET,
            "auth_uri": "https://accounts.google.com/o/oauth2/auth",
            "token_uri": "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
            "redirect_uris": [
                "https://pdf-reader-projectperiexom.replit.app/youtube/callback"
            ],
            "javascript_origins": [
                "https://pdf-reader-projectperiexom.replit.app"
            ]
        }
    }

def get_oauth_flow():
    """Create and return an OAuth2 flow object."""
    flow = Flow.from_client_config(
        client_config=get_client_config(),
        scopes=SCOPES,
        state=os.urandom(16).hex()
    )
    flow.redirect_uri = 'https://pdf-reader-projectperiexom.replit.app/youtube/callback'
    return flow

def get_youtube_client(credentials):
    """Build and return a YouTube API client using provided credentials."""
    try:
        return googleapiclient.discovery.build(
            API_SERVICE_NAME, 
            API_VERSION, 
            credentials=credentials,
            cache_discovery=False
        )
    except Exception as e:
        current_app.logger.error(f"Error building YouTube client: {e}")
        return None

def search_videos(youtube, query, max_results=10):
    """Search for videos on YouTube based on the query."""
    try:
        request = youtube.search().list(
            part="snippet",
            maxResults=max_results,
            q=query,
            type="video"
        )
        return request.execute()
    except Exception as e:
        current_app.logger.error(f"Error searching videos: {e}")
        return {"error": str(e), "items": []}

def get_video_details(youtube, video_id):
    """Get detailed information about a specific video."""
    try:
        request = youtube.videos().list(
            part="snippet,contentDetails,statistics",
            id=video_id
        )
        return request.execute()
    except Exception as e:
        current_app.logger.error(f"Error getting video details: {e}")
        return None
