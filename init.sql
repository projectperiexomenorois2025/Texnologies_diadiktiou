-- Δημιουργία πινάκων για τη βάση δεδομένων Streamify

-- Πίνακας χρηστών
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(256) NOT NULL,
    first_name VARCHAR(64) NOT NULL,
    last_name VARCHAR(64) NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Πίνακας λιστών αναπαραγωγής
CREATE TABLE playlists (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    title VARCHAR(128) NOT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Πίνακας βίντεο
CREATE TABLE videos (
    id SERIAL PRIMARY KEY,
    playlist_id INTEGER NOT NULL REFERENCES playlists(id),
    youtube_id VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    added_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Πίνακας ακολούθησης
CREATE TABLE followers (
    follower_id INTEGER NOT NULL REFERENCES users(id),
    following_id INTEGER NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id)
);

-- Εισαγωγή δεδομένων από το εξαγόμενο αρχείο

-- Πίνακας Users
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (1, 'admin', 'admin@example.com', 'scrypt:32768:8:1$XOgwx5Oo5Xwriiaa$b72d00a7b37a699ae0ea95cab29663970522ba0267e103b7328454838c4baac195b7d0b9dede53aab2fc2ae362b483a5561629d0b8bf727d907312c0c51928a5', 'Admin', 'User', '2025-04-22 16:34:06.574589');
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (2, 'test', 'test@gmail.com', 'scrypt:32768:8:1$Y1lOUQse4ACbKPfM$74390a56114bafbdbe14acd43bacd6fcccd229e938d00146afc4917149e2cffa951d5c19c3f4831e13f06059079337fa6f4cf337ab8b43d532abd59e6aa08d4d', 'test', 'test', '2025-04-22 16:34:58.313479');
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (4, 'testuser', 'testuser@gmail.com', 'scrypt:32768:8:1$RsRPHsrRbydg20Hz$8840f757891bd02908f248fd71d2e08c3a9756583847b1c357b80f14f2498852b17ef0d88c6dddbc0d951ce07c60044c972a7dddd1b950ba13212ffb34997f5a', 'test', 'test2', '2025-04-25 16:32:42.716560');
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (5, 'Learner1', 'learner1@gmail.com', 'scrypt:32768:8:1$GCRf7MyJNxyMdGk9$061efd9bdbf647411d7eaf56b6a6bda92983c9dce377a1f450eff811f92a2d3d29df39a5d2754ae8c52c7aa2a768aa93ed48ca573e954d439c9defdc963828d5', 'Thomas', 'Kissas', '2025-05-21 10:03:32.163745');
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (6, 'Learner2', 'Learner2@gmail.com', 'scrypt:32768:8:1$De1SpW9llpE8bkwH$f08c2d27df7f6f9b8adee5474e91ea254c7b50cc6ddd8b4052196f9230959e8d91f1d14de37c3ee59287d410181923d4bdef1222bcabd66df54f45ab89098fbd', 'Learner2', 'Learner2', '2025-05-21 17:05:53.270569');
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (7, 'Learner3', 'Learner3@gmail.com', 'scrypt:32768:8:1$GcWloAG6mWUIAIjh$7853e72b1f45672a40300f7aaa032fda0a812785111ab96a6ff7996aea0bf84177c0754a90c3a9e8c076fd58e7e53a49e30e89f011e782b19cce14d3b914f6da', 'Learner3', 'Learner3', '2025-05-21 17:38:17.428737');
INSERT INTO users (id, username, email, password_hash, first_name, last_name, created_at) VALUES (8, 'Learner4', 'projectperiexomenorois2025@gmail.com', 'scrypt:32768:8:1$lykM1ltyFrSLXYhT$a765cdd72f75bb38324ed9b1b04696f35dd3655aa1b1316a7afe8b3c8b8247c8283e6659787b8c596fb9e5fbd53e1fa70bac2a8e629572d1a92bde5a621f5a9e', 'Learner4', 'Learner4', '2025-05-22 04:29:35.136338');

-- Πίνακας Playlists
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (1, 2, 'Test_Playlist', TRUE, '2025-04-22 16:35:19.647465');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (2, 2, 'New Playlist', TRUE, '2025-04-22 16:38:27.245335');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (3, 2, 'test3', TRUE, '2025-04-22 17:49:35.268304');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (4, 4, 'testlist', TRUE, '2025-04-25 16:33:13.376994');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (5, 4, 'test2', TRUE, '2025-04-25 16:49:10.523054');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (6, 5, 'test', TRUE, '2025-05-21 10:03:47.931773');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (7, 5, 'new_playliste', TRUE, '2025-05-21 10:40:41.838815');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (8, 5, 'Youtubenew', TRUE, '2025-05-21 16:56:55.650745');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (9, 6, 'test', TRUE, '2025-05-21 17:06:09.557319');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (10, 6, 'youtube', TRUE, '2025-05-21 17:15:42.134571');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (11, 7, 'Youtubenew', TRUE, '2025-05-21 17:38:31.882007');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (12, 7, 'test', TRUE, '2025-05-21 17:42:19.325175');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (13, 6, '121', TRUE, '2025-05-21 17:44:47.274508');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (14, 6, '1212', TRUE, '2025-05-21 17:48:02.162073');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (15, 6, 'test', TRUE, '2025-05-21 17:52:52.178812');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (16, 6, '10000', TRUE, '2025-05-21 17:54:14.631878');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (17, 5, 'τεστ', TRUE, '2025-05-22 03:53:30.656824');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (18, 8, 'mylist', TRUE, '2025-05-22 04:30:07.520377');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (19, 8, 'test1234', TRUE, '2025-05-22 04:37:43.356368');
INSERT INTO playlists (id, user_id, title, is_public, created_at) VALUES (20, 8, 'NewList', TRUE, '2025-05-22 05:43:53.839750');

-- Πίνακας Videos
INSERT INTO videos (id, playlist_id, youtube_id, title, added_at) VALUES (1, 2, 'dQw4w9WgXcQ', 'Rick Astley - Never Gonna Give You Up (Official Music Video)', '2025-04-22 16:38:43.878313');
INSERT INTO videos (id, playlist_id, youtube_id, title, added_at) VALUES (2, 15, 'dQw4w9WgXcQ', 'τεστ', '2025-05-21 17:53:09.167701');
INSERT INTO videos (id, playlist_id, youtube_id, title, added_at) VALUES (3, 19, 'hpQLYOs7c5w&ab', 'τεστ', '2025-05-22 05:41:55.399452');
INSERT INTO videos (id, playlist_id, youtube_id, title, added_at) VALUES (4, 19, '4_kek6_EGBw', 'τεστ', '2025-05-22 05:43:03.305393');
INSERT INTO videos (id, playlist_id, youtube_id, title, added_at) VALUES (5, 20, '4_kek6_EGBw', 'video', '2025-05-22 05:44:10.664048');

-- Πίνακας Followers
INSERT INTO followers (follower_id, following_id, created_at) VALUES (8, 6, '2025-05-22 06:05:53.694795');

-- Ρύθμιση ακολουθίας serial για να συνεχίζει από το μέγιστο ID
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('playlists_id_seq', (SELECT MAX(id) FROM playlists));
SELECT setval('videos_id_seq', (SELECT MAX(id) FROM videos));