-- SPDX-License-Identifier: GPL-2.0-only
--
-- This file is part of Nominatim. (https://nominatim.org)
--
-- Copyright (C) 2022 by the Nominatim developer community.
-- For a full list of authors see the git log.

DROP TABLE IF EXISTS word;
CREATE TABLE word (
  word_id INTEGER,
  word_token text NOT NULL,
  type text NOT NULL,
  word text,
  info jsonb
) {{db.tablespace.search_data}};

CREATE INDEX idx_word_word_token ON word
    USING BTREE (word_token) {{db.tablespace.search_index}};
-- Used when updating country names from the boundary relation.
CREATE INDEX idx_word_country_names ON word
    USING btree(word) {{db.tablespace.address_index}}
    WHERE type = 'C';
-- Used when inserting new postcodes on updates.
CREATE INDEX idx_word_postcodes ON word
    USING btree(word) {{db.tablespace.address_index}}
    WHERE type = 'P';
-- Used when inserting full words.
CREATE INDEX idx_word_full_word ON word
    USING btree(word) {{db.tablespace.address_index}}
    WHERE type = 'W';
-- Used when inserting analyzed housenumbers (exclude old-style entries).
CREATE INDEX idx_word_housenumbers ON word
    USING btree(word) {{db.tablespace.address_index}}
    WHERE type = 'H' and word is not null;

GRANT SELECT ON word TO "{{config.DATABASE_WEBUSER}}";

DROP SEQUENCE IF EXISTS seq_word;
CREATE SEQUENCE seq_word start 1;
GRANT SELECT ON seq_word to "{{config.DATABASE_WEBUSER}}";
