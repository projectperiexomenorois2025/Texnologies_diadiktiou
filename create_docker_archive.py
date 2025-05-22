import os
import zipfile
import shutil
import sys

def create_docker_archive():
    """
    Δημιουργεί ένα αρχείο zip που περιέχει όλα τα αρχεία που χρειάζονται
    για το dockerised web app.
    """
    # Ορισμός του ονόματος του αρχείου zip
    zip_filename = "streamify_docker_app.zip"
    
    # Λίστα αρχείων και φακέλων που θα συμπεριληφθούν στο zip
    include_files = [
        "Dockerfile",
        "docker-compose.yml",
        "requirements_docker.txt",
        "init.sql",
        "README.md",
        ".env-example",
        "main.py",
        "models.py",
        "youtube_api.py"
    ]
    
    include_dirs = [
        "static",
        "templates"
    ]
    
    # Αφαίρεση παλαιού αρχείου zip αν υπάρχει
    if os.path.exists(zip_filename):
        os.remove(zip_filename)
    
    # Δημιουργία του zip αρχείου
    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        # Προσθήκη μεμονωμένων αρχείων
        for file in include_files:
            if os.path.exists(file):
                zipf.write(file)
                print(f"Added file: {file}")
            else:
                print(f"Warning: File {file} does not exist")
        
        # Προσθήκη όλων των αρχείων από τους επιλεγμένους φακέλους
        for directory in include_dirs:
            if os.path.exists(directory):
                for root, dirs, files in os.walk(directory):
                    for file in files:
                        file_path = os.path.join(root, file)
                        arcname = file_path  # Διατήρηση της ίδιας δομής φακέλων στο zip
                        zipf.write(file_path, arcname)
                print(f"Added directory: {directory}")
            else:
                print(f"Warning: Directory {directory} does not exist")
    
    print(f"\nArchive created: {zip_filename}")
    print(f"Size: {os.path.getsize(zip_filename) / (1024*1024):.2f} MB")
    
    return zip_filename

if __name__ == "__main__":
    create_docker_archive()