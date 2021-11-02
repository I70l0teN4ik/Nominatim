Nominatim comes with a predefined set of configuration options that should
work for most standard installations. If you have special requirements, there
are many places where the configuration can be adapted. This chapter describes
the following configurable parts:

* [Global Settings](Settings.md) has a detailed description of all parameters that
  can be set in your local `.env` configuration
* [Import styles](Import-Styles.md) explains how to write your own import style
  in order to control what kind of OSM data will be imported
* [Place ranking](Ranking.md) describes the configuration around classifing
  places in terms of their importance and their role in an address
* [Tokenizers](Tokenizers.md) describes the configuration of the module
  responsible for analysing and indexing names
* [Special Phrases](Special-Phrases.md) are common nouns or phrases that
  can be used in search to identify a class of places

There are also guides for adding the following external data:

* [US house numbers from the TIGER dataset](Tiger.md)
* [External postcodes](Postcodes.md)
