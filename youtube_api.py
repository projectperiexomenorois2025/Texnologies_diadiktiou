
import os
import googleapiclient.discovery
import googleapiclient.errors
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import Flow
from flask import current_app, url_for

# OAuth credentials
CLIENT_ID = '101637104101-m51bljq3iaj8fcd4t57lcrks4jevefvo.apps.googleusercontent.com'
CLIENT_SECRET = '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'
API_KEY = 'AIzaSyBedv6w44veki4fnSbBQL2OfsJ0RTM2XtU'

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
    return {
        "web": {
            "client_id": CLIENT_ID,
            "client_secret": CLIENT_SECRET,
            "auth_uri": "https://accounts.google.com/o/oauth2/auth",
            "token_uri": "https://oauth2.googleapis.com/token",
            "redirect_uris": [
                "https://dfbc0bf8-a801-4318-99c6-963dc9419c92-00-3us2ncr363jxw.worf.replit.dev/youtube/callback",
                "https://dfbc0bf8-a801-4318-99c6-963dc9419c92-00-3us2ncr363jxw.worf.replit.dev/oauth2callback"
            ]
        }
    }

def get_oauth_flow():
    """Create and return an OAuth2 flow object."""
    if not CLIENT_ID or not CLIENT_SECRET:
        raise ValueError("Missing OAuth credentials. Please set GOOGLE_OAUTH_CLIENT_ID and GOOGLE_OAUTH_CLIENT_SECRET in secrets.")
    
    flow = Flow.from_client_config(
        client_config=get_client_config(),
        scopes=SCOPES
    )
    flow.redirect_uri = url_for('youtube_oauth_callback', _external=True)
    return flow

def get_youtube_client(credentials):
    """Build and return a YouTube API client using provided credentials."""
    try:
        youtube = googleapiclient.discovery.build(
            API_SERVICE_NAME, API_VERSION, credentials=credentials
        )
        return youtube
    except Exception as e:
        current_app.logger.error(f"Error building YouTube client: {e}")
        raise Exception(f"Failed to initialize YouTube client: {str(e)}")

def search_videos(youtube, query, max_results=10):
    """Search for videos on YouTube based on the query."""
    try:
        request = youtube.search().list(
            part="snippet",
            q=query,
            type="video",
            maxResults=max_results
        )
        response = request.execute()
        return response
    except googleapiclient.errors.HttpError as e:
        current_app.logger.error(f"Error searching YouTube: {e}")
        return {"items": []}

def get_video_details(youtube, video_id):
    """Get detailed information about a specific video."""
    try:
        request = youtube.videos().list(
            part="snippet,contentDetails,statistics",
            id=video_id
        )
        response = request.execute()
        return response
    except googleapiclient.errors.HttpError as e:
        current_app.logger.error(f"Error getting video details: {e}")
        return None
