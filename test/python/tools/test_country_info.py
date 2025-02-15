# SPDX-License-Identifier: GPL-2.0-only
#
# This file is part of Nominatim. (https://nominatim.org)
#
# Copyright (C) 2022 by the Nominatim developer community.
# For a full list of authors see the git log.
"""
Tests for function that handle country properties.
"""

import pytest

from nominatim.tools import country_info


def read_config(def_config):
    country_info.setup_country_config(def_config)


@pytest.mark.parametrize("no_partitions", (True, False))
def test_setup_country_tables(src_dir, temp_db_with_extensions, dsn, temp_db_cursor,
                              def_config, no_partitions):
    read_config(def_config)
    country_info.setup_country_tables(dsn, src_dir / 'data', no_partitions)

    assert temp_db_cursor.table_exists('country_name')
    assert temp_db_cursor.table_rows('country_name') == \
        temp_db_cursor.scalar(
            'SELECT count(DISTINCT country_code) FROM country_name')

    partitions = temp_db_cursor.row_set(
        "SELECT DISTINCT partition FROM country_name")
    if no_partitions:
        assert partitions == {(0, )}
    else:
        assert len(partitions) > 10

    assert temp_db_cursor.table_exists('country_osm_grid')
    assert temp_db_cursor.table_rows('country_osm_grid') > 100


@pytest.mark.parametrize("languages", (None, ' fr,en'))
def test_create_country_names(temp_db_with_extensions, temp_db_conn, temp_db_cursor,
                              table_factory, tokenizer_mock, languages, def_config):
    read_config(def_config)

    table_factory('country_name', 'country_code varchar(2), name hstore',
                  content=(('us', '"name"=>"us1","name:af"=>"us2"'),
                           ('fr', '"name"=>"Fra", "name:en"=>"Fren"')))

    assert temp_db_cursor.scalar("SELECT count(*) FROM country_name") == 2

    tokenizer = tokenizer_mock()

    country_info.create_country_names(temp_db_conn, tokenizer, languages)

    assert len(tokenizer.analyser_cache['countries']) == 2

    result_set = {k: set(v.values())
                  for k, v in tokenizer.analyser_cache['countries']}

    if languages:
        assert result_set == {'us': set(('us', 'us1', 'United States')),
                              'fr': set(('fr', 'Fra', 'Fren'))}
    else:
        assert result_set == {'us': set(('us', 'us1', 'us2', 'United States')),
                              'fr': set(('fr', 'Fra', 'Fren'))}


def test_setup_country_config_languages_not_loaded(project_env):
    (project_env.project_dir / 'country_settings.yaml').write_text("""
de:
    partition: 3
    names:
        name:
            default: Deutschland
""")
    country_info._COUNTRY_INFO._info = None
    country_info.setup_country_config(project_env)
    assert country_info._COUNTRY_INFO._info == {'de': {'partition': 3,
                                                       'languages': [], 'names': {'name': {'default': 'Deutschland'}}}}


def test_setup_country_config_name_not_loaded(project_env):
    (project_env.project_dir / 'country_settings.yaml').write_text("""
de:
    partition: 3
    languages: de
    names:
""")
    country_info._COUNTRY_INFO._info = None
    country_info.setup_country_config(project_env)
    assert country_info._COUNTRY_INFO._info == {'de': {'partition': 3,
                                                       'languages': ['de'], 'names': {'name': {}}}}


def test_setup_country_config_names_not_loaded(project_env):
    (project_env.project_dir / 'country_settings.yaml').write_text("""
de:
    partition: 3
    languages: de
""")
    country_info._COUNTRY_INFO._info = None
    country_info.setup_country_config(project_env)
    assert country_info._COUNTRY_INFO._info == {'de': {'partition': 3,
                                                       'languages': ['de'], 'names': {'name': {}}}}


def test_setup_country_config_special_character(project_env):
    (project_env.project_dir / 'country_settings.yaml').write_text("""
bq:
    partition: 250
    languages: nl
    names: 
        name: 
            default: "\\N"
""")
    country_info._COUNTRY_INFO._info = None
    country_info.setup_country_config(project_env)
    assert country_info._COUNTRY_INFO._info == {'bq': {'partition': 250,
                                                       'languages': ['nl'], 'names': {'name': {'default': '\x85'}}}}
