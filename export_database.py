import os
import sys
import datetime

# Προσθέτουμε το τρέχον directory στο path για να μπορούμε να εισάγουμε τα models
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from models import db, User, Playlist, Video, Follower
from main import app

def export_database():
    """
    Εξάγει όλα τα δεδομένα της βάσης σε SQL εντολές.
    Η εξαγωγή γίνεται με τρόπο ώστε να μπορούν τα δεδομένα 
    να εισαχθούν σε νέα βάση δεδομένων.
    """
    with app.app_context():
        # Δημιουργία του αρχείου εξαγωγής
        timestamp = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"database_export_{timestamp}.sql"
        
        with open(filename, 'w', encoding='utf-8') as f:
            # Προσθήκη σχολίων στο αρχείο
            f.write("-- Εξαγωγή βάσης δεδομένων Streamify\n")
            f.write(f"-- Ημερομηνία εξαγωγής: {datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write("-- Αυτό το αρχείο περιέχει εντολές SQL για την αναδημιουργία της βάσης δεδομένων\n\n")
            
            # Εξαγωγή πινάκων
            # 1. Πίνακας Users
            f.write("-- Πίνακας Users\n")
            users = User.query.all()
            for user in users:
                f.write(f"INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES "
                        f"({user.id}, '{user.username}', '{user.email}', '{user.password_hash}', "
                        f"'{user.first_name}', '{user.last_name}', '{user.created_at}');\n")
            f.write("\n")
            
            # 2. Πίνακας Playlists
            f.write("-- Πίνακας Playlists\n")
            playlists = Playlist.query.all()
            for playlist in playlists:
                f.write(f"INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES "
                        f"({playlist.id}, {playlist.user_id}, '{playlist.title}', "
                        f"{'TRUE' if playlist.is_public else 'FALSE'}, '{playlist.created_at}');\n")
            f.write("\n")
            
            # 3. Πίνακας Videos
            f.write("-- Πίνακας Videos\n")
            videos = Video.query.all()
            for video in videos:
                safe_title = video.title.replace("'", "''")
                f.write(f"INSERT INTO videos (id, playlist_id, youtube_id, title, added_at) VALUES "
                        f"({video.id}, {video.playlist_id}, '{video.youtube_id}', "
                        f"'{safe_title}', '{video.added_at}');\n")
            f.write("\n")
            
            # 4. Πίνακας Followers
            f.write("-- Πίνακας Followers\n")
            followers = Follower.query.all()
            for follower in followers:
                f.write(f"INSERT INTO followers (follower_id, following_id, created_at) VALUES "
                        f"({follower.follower_id}, {follower.following_id}, '{follower.created_at}');\n")
        
        print(f"Η βάση δεδομένων εξήχθη επιτυχώς στο αρχείο '{filename}'")
        return filename

if __name__ == "__main__":
    export_database()