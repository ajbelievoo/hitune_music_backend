ALTER TABLE `_u_list` DROP `s_managed_artists`;
DELETE FROM `_u_roles` WHERE type = 'artist';
DROP TABLE `_c_m_cronjobs_spotify`, `_c_m_events`, `_c_m_genres_hiearchy`;
