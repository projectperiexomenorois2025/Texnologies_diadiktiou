# Streamify - YouTube Playlist Manager

Μια διαδικτυακή εφαρμογή για τη δημιουργία, διαχείριση και κοινή χρήση λιστών αναπαραγωγής YouTube.

## Χαρακτηριστικά

- Δημιουργία λογαριασμού και διαχείριση προφίλ
- Δημιουργία και επεξεργασία λιστών αναπαραγωγής
- Αναζήτηση και προσθήκη βίντεο από το YouTube
- Αναπαραγωγή βίντεο απευθείας στην εφαρμογή
- Ακολούθηση άλλων χρηστών και προβολή των δημόσιων λιστών τους
- Εξαγωγή λιστών αναπαραγωγής σε μορφή JSON

## Εκτέλεση με Docker

Η εφαρμογή είναι dockerized και μπορεί εύκολα να εκτελεστεί με το Docker Compose.

### Προαπαιτούμενα

- Docker και Docker Compose εγκατεστημένα στο σύστημά σας
- Google API credentials για YouTube Data API (προαιρετικά)

### Εκκίνηση της εφαρμογής

1. Αντιγράψτε το repository σε τοπικό φάκελο
2. Δημιουργήστε ένα αρχείο `.env` με τα Google OAuth credentials (προαιρετικά):
   ```
   GOOGLE_OAUTH_CLIENT_ID=your_client_id
   GOOGLE_OAUTH_CLIENT_SECRET=your_client_secret
   ```
3. Εκτελέστε την εντολή:
   ```
   docker compose up
   ```
4. Η εφαρμογή θα είναι διαθέσιμη στη διεύθυνση: http://localhost:5000

### Σταματώντας την εφαρμογή

Για να σταματήσετε την εφαρμογή, πιέστε Ctrl+C στο τερματικό ή εκτελέστε:
```
docker compose down
```

## Προεπιλεγμένοι λογαριασμοί

Η εφαρμογή περιλαμβάνει προεπιλεγμένους λογαριασμούς για δοκιμές:

- Username: `admin`, Password: `password`
- Username: `test`, Password: `password`

## Πηγαίος κώδικας και Τεχνολογίες

- Backend: Python με Flask
- Database: PostgreSQL
- Frontend: HTML/CSS/JavaScript
- API Integration: YouTube Data API