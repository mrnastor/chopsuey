<?php

/* VALIDATION VALUES */
/*
	1 - Validate if id exists on DB table
	2 - Validate INT value, CANNOT be null
	3 - Validate INT value, CAN be null
	4 - Validate string NOT NULL
	5 - Validate BOOLEAN, allowed values 1 or 0
	6 - Validate timestamp NOT NULL <YYYYMMDDHHss>
	7 - Validate timestamp CAN BE NULL <YYYYMMDDHHss>
	8 - Validate URL 
*/

define( 'XMLTEMPLATE_VAL_DB_ID',			1 );
define( 'XMLTEMPLATE_VAL_INT_NOT_NULL', 	2 );
define( 'XMLTEMPLATE_VAL_INT_CAN_NULL', 	3 );
define( 'XMLTEMPLATE_VAL_STRING_NOT_NULL', 	4 );
define( 'XMLTEMPLATE_VAL_BOOLEAN_1_0', 		5 );
define( 'XMLTEMPLATE_VAL_TSTAMP_NOT_NULL',	6 );
define( 'XMLTEMPLATE_VAL_TSTAMP_CAN_NULL',	7 );
define( 'XMLTEMPLATE_VAL_URL',				8 );
define( 'XMLTEMPLATE_VAL_YES_NO',			9 );
define( 'XMLTEMPLATE_VAL_RELATED_NEWS',		10 );

define( 'XMLTEMPLATE_ATTR_ARRAY_OMIT_OK',	1 );
define( 'XMLTEMPLATE_ATTR_ARRAY_OMIT_NG',	2 );


/*
CREATE TABLE sportslife_content (
	id SERIAL,
	PRIMARY KEY(id),
	title VARCHAR(150) NOT NULL,
	title_kana VARCHAR(300),
	genre_id INT NOT NULL,
	description VARCHAR(200),
	movie_play_sec INT NOT NULL,
	orig_filename VARCHAR(128),
	thumb_filename VARCHAR(128) NOT NULL,
	thumb_filepath VARCHAR(128) NOT NULL,
	thumb_basename VARCHAR(128) NOT NULL,
	copyright VARCHAR(512) NOT NULL,
	published_datetime TIMESTAMP,
	free BOOLEAN NOT NULL,
	sort_num INT,
	meta_str_datetime TIMESTAMP NOT NULL,
	meta_end_datetime TIMESTAMP NOT NULL,
	status INT NOT NULL DEFAULT '0',
	content_source VARCHAR(512) NOT NULL,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP
);
*/
$templateContent = array(
	'table_name' => "sportslife_content",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'content'				,	''						,	-1								,	''								,	''						),
		array(	'distribution'			,	'content'				,	-1								,	''								,	''						),
		array(	'id'					,	'content'				,	XMLTEMPLATE_VAL_DB_ID			,	''								,	'id'					),
		array(	'disabled_flag'			,	'content'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'title'					,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'title'					),
		array(	'title_kana'			,	'content'				,	-1								,	''								,	'title_kana'			),
		array(	'genre_id'				,	'content'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'genre_id'				),
		array(	'description'			,	'content'				,	-1								,	''								,	'description'			),
		array(	'movie_play_sec'		,	'content'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'movie_play_sec'		),
		array(	'orig_filename'			,	'content'				,	-1								,	''								,	'orig_filename'			),
		array(	'thumb_filename'		,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'thumb_filename'		),
		array(	'thumb_filepath'		,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'thumb_filepath'		),
		array(	'thumb_basename'		,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'thumb_basename'		),
		array(	'copyright'				,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'copyright'				),
		array(	'published_datetime'	,	'content'				,	XMLTEMPLATE_VAL_TSTAMP_CAN_NULL	,	''								,	'published_datetime'	),
		array(	'content_source'		,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'content_source'		),
		array(	'caption'				,	'content'				,	-1								,	''								,	'caption'				),
		array(	'status'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				),
		array(	'free'					,	'distribution'			,	XMLTEMPLATE_VAL_BOOLEAN_1_0		,	''								,	'free'					),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				),
		array(	'meta_str_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_str_datetime'		),
		array(	'meta_end_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_end_datetime'		)
	)
);


/*
CREATE TABLE sportslife_content_filter (
	id SERIAL,
	PRIMARY KEY(id),
	content_id INT NOT NULL,
	filter_id INT NOT NULL,
	sort_num INT,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (content_id) REFERENCES sportslife_content(id)
);
*/
$templateContentFilter = array(
	'table_name' => "sportslife_content_filter",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'content'				,	''						,	-1								,	''								,	''						),
		array(	'filters'				,	'content'				,	-1								,	''								,	''						),
		array(	'distribution'			,	'content'				,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'content'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'content_id'			,	'filters'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'content_id'			),
		array(	'filter_id'				,	'filters'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'filter_id'				),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				)
	)
);

/*
CREATE TABLE sportslife_content_subgenre (
	id SERIAL,
	PRIMARY KEY(id),
	content_id  INT NOT NULL,
	subgenre_id INT NOT NULL,
	sort_num INT,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (content_id) REFERENCES sportslife_content(id),
	FOREIGN KEY (subgenre_id) REFERENCES sportslife_subgenre(id)
);
*/
$templateContentSubgenre = array(
	'table_name' => "sportslife_content_subgenre",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'content'				,	''						,	-1								,	''								,	''						),
		array(	'subgenres'				,	'content'				,	-1								,	''								,	''						),
		array(	'distribution'			,	'content'				,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'content'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'content_id'			,	'subgenres'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'content_id'			),
		array(	'subgenre_id'			,	'subgenres'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'subgenre_id'			),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				)
	)
);

/*
CREATE TABLE sportslife_content_movie (
	id SERIAL,
	PRIMARY KEY(id),
	content_id INT NOT NULL,
	device_type INT NOT NULL,
	filename VARCHAR(128) NOT NULL,
	filepath VARCHAR(128) NOT NULL,
	filesize INT NOT NULL,
	sort_num INT,
	status INT NOT NULL DEFAULT '0',
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (content_id) REFERENCES sportslife_content(id)
);
*/

// Template for コンテンツ movie.php
$templateContentMovie = array(
	'table_name' => "sportslife_content_movie",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'content'				,	''						,	-1								,	''								,	''						),
		array(	'movies'				,	'content'				,	-1								,	''								,	''						),
		array(	'movie'					,	'movies'				,	-1								,	''								,	''						),
		array(	'distribution'			,	'content'				,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'content'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'content_id'			,	'movie'					,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'content_id'			),
		array(	'device_type'			,	'movie'					,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'device_type'			),
		array(	'filename'				,	'movie'					,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'filename'				),
		array(	'filepath'				,	'movie'					,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'filepath'				),
		array(	'filesize'				,	'movie'					,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'filesize'				),
		array(	'status'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				)
	)
);


/*
CREATE TABLE sportslife_movie_related_news (
	id SERIAL,
	PRIMARY KEY(id),
	news_id INT NOT NULL,
	content_id INT NOT NULL,
	sort_num INT,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (news_id) REFERENCES sportslife_news(id),
	FOREIGN KEY (content_id) REFERENCES sportslife_content(id)
);
*/
$templateMovieRelatedNews = array(
	'table_name' => "sportslife_movie_related_news",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'content'				,	''						,	-1								,	''								,	''						),
		array(	'related_news'			,	'content'				,	-1								,	''								,	''						),
		array(	'distribution'			,	'content'				,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'content'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'news_id'				,	'related_news'			,	XMLTEMPLATE_VAL_RELATED_NEWS	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'news_id'				),
		array(	'content_id'			,	'related_news'			,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'content_id'			),
		array(	'status'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				)
	)
);


/*
CREATE TABLE sportslife_news (
	id SERIAL,
	PRIMARY KEY(id),
	genre_id INT NOT NULL,
	internal_news_id VARCHAR(64),
	title VARCHAR(128) NOT NULL,
	title_kana VARCHAR(256),
	body TEXT NOT NULL,
	thumb_filename VARCHAR(128),
	thumb_filepath VARCHAR(128),
	thumb_basename VARCHAR(128),
	caption VARCHAR(128),
	copyright VARCHAR(128),
	news_source INT NOT NULL,
	published_datetime TIMESTAMP,
	free BOOLEAN NOT NULL,
	sort_num INT,
	meta_str_datetime TIMESTAMP NOT NULL,
	meta_end_datetime TIMESTAMP NOT NULL,
	status INT NOT NULL DEFAULT '0',
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP
);
*/

// Template for ニュース news.php
$templateNews = array(
	'table_name' => "sportslife_news",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'news'					,	''						,	-1								,	''								,	''						),
		array(	'distribution'			,	'news'					,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'news'					,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'id'					,	'news'					,	XMLTEMPLATE_VAL_DB_ID			,	''								,	'id'					),
		array(	'genre_id'				,	'news'					,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'genre_id'				),
		array(	'internal_news_id'		,	'news'					,	-1								,	''								,	'internal_news_id'		),
		array(	'title'					,	'news'					,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'title'					),
		array(	'title_kana'			,	'news'					,	-1								,	''								,	'title_kana'			),
		array(	'body'					,	'news'					,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'body'					),
		array(	'thumb_filename'		,	'news'					,	-1								,	''								,	'thumb_filename'		),
		array(	'thumb_filepath'		,	'news'					,	-1								,	''								,	'thumb_filepath'		),
		array(	'thumb_basename'		,	'news'					,	-1								,	''								,	'thumb_basename'		),
		array(	'caption'				,	'news'					,	-1								,	''								,	'caption'				),
		array(	'copyright'				,	'news'					,	-1								,	''								,	'copyright'				),
		array(	'news_source'			,	'news'					,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'news_source'			),
		array(	'published_datetime'	,	'news'					,	XMLTEMPLATE_VAL_TSTAMP_CAN_NULL	,	''								,	'published_datetime'	),
		array(	'status'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				),
		array(	'free'					,	'distribution'			,	XMLTEMPLATE_VAL_BOOLEAN_1_0		,	''								,	'free'					),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				),
		array(	'meta_str_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_str_datetime'		),
		array(	'meta_end_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_end_datetime'		)
	)
);


/*
CREATE TABLE sportslife_news_filter (
	id SERIAL,
	PRIMARY KEY(id),
	news_id INT NOT NULL,
	filter_id INT NOT NULL,
	sort_num INT,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (news_id) REFERENCES sportslife_news(id)
);
*/
$templateNewsFilter = array(
	'table_name' => "sportslife_news_filter",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'news'					,	''						,	-1								,	''								,	''						),
		array(	'distribution'			,	'news'					,	-1								,	''								,	''						),
		array(	'filters'				,	'news'					,	-1								,	''								,	''						),
		array(	'distribution'			,	'news'					,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'news'					,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'news_id'				,	'filters'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'news_id'				),
		array(	'filter_id'				,	'filters'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'filter_id'				),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				)
	)
);

/*
CREATE TABLE sportslife_movie_related_news (
	id SERIAL,
	PRIMARY KEY(id),
	news_id INT NOT NULL,
	content_id INT NOT NULL,
	sort_num INT,
	status INT NOT NULL DEFAULT '0',
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (news_id) REFERENCES sportslife_news(id),
	FOREIGN KEY (content_id) REFERENCES sportslife_content(id)
);
*/
$templateNewsRelatedMovie = array(
	'table_name' => "sportslife_movie_related_news",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'news'					,	''						,	-1								,	''								,	''						),
		array(	'movies'				,	'news'					,	-1								,	''								,	''						),
		array(	'distribution'			,	'news'					,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'news'					,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'news_id'				,	'movies'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'news_id'				),
		array(	'related_content_id'	,	'movies'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	XMLTEMPLATE_ATTR_ARRAY_OMIT_OK	,	'content_id'			),
		array(	'status'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				)
	)
);

/*
CREATE TABLE sportslife_column (
	id SERIAL,
	PRIMARY KEY(id),
	genre_id INT NOT NULL,
	title VARCHAR(128) NOT NULL,
	title_kana VARCHAR(256),
	columnist_id INT,
	body TEXT NOT NULL,
	copyright VARCHAR(128) NOT NULL,
	published_datetime TIMESTAMP,
	free BOOLEAN NOT NULL,
	sort_num INT,
	meta_str_datetime TIMESTAMP NOT NULL,
	meta_end_datetime TIMESTAMP NOT NULL,
	thumb_filename1 VARCHAR(128),
	thumb_filename2 VARCHAR(128),
	thumb_filename3 VARCHAR(128),
	thumb_filename4 VARCHAR(128),
	thumb_filepath1 VARCHAR(128),
	thumb_filepath2 VARCHAR(128),
	thumb_filepath3 VARCHAR(128),
	thumb_filepath4 VARCHAR(128),
	thumb_basename1 VARCHAR(128),
	thumb_basename2 VARCHAR(128),
	thumb_basename3 VARCHAR(128),
	thumb_basename4 VARCHAR(128),
	status INT NOT NULL DEFAULT '0',
	column_source VARCHAR(512) NOT NULL,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (columnist_id) REFERENCES sportslife_columnist(id)
);
*/

// Template for コラム column.php
$templateColumn = array(
	'table_name' => "sportslife_column",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'column'				,	''						,	-1								,	''								,	''						),
		array(	'distribution'			,	'column'				,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'column'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'id'					,	'column'				,	XMLTEMPLATE_VAL_DB_ID			,	''								,	'id'					),
		array(	'genre_id'				,	'column'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'genre_id'				),
		array(	'title'					,	'column'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'title'					),
		array(	'title_kana'			,	'column'				,	-1								,	''								,	'title_kana'			),
		array(	'columnist_id'			,	'column'				,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'columnist_id'			),
		array(	'body'					,	'column'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'body'					),
		array(	'copyright'				,	'column'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'copyright'				),
		array(	'published_datetime'	,	'column'				,	XMLTEMPLATE_VAL_TSTAMP_CAN_NULL	,	''								,	'published_datetime'	),
		array(	'thumb_filename1'		,	'column'				,	-1								,	''								,	'thumb_filename1'		),
		array(	'thumb_filename2'		,	'column'				,	-1								,	''								,	'thumb_filename2'		),
		array(	'thumb_filename3'		,	'column'				,	-1								,	''								,	'thumb_filename3'		),
		array(	'thumb_filename4'		,	'column'				,	-1								,	''								,	'thumb_filename4'		),
		array(	'thumb_filepath1'		,	'column'				,	-1								,	''								,	'thumb_filepath1'		),
		array(	'thumb_filepath2'		,	'column'				,	-1								,	''								,	'thumb_filepath2'		),
		array(	'thumb_filepath3'		,	'column'				,	-1								,	''								,	'thumb_filepath3'		),
		array(	'thumb_filepath4'		,	'column'				,	-1								,	''								,	'thumb_filepath4'		),
		array(	'thumb_basename1'		,	'column'				,	-1								,	''								,	'thumb_basename1'		),
		array(	'thumb_basename2'		,	'column'				,	-1								,	''								,	'thumb_basename2'		),
		array(	'thumb_basename3'		,	'column'				,	-1								,	''								,	'thumb_basename3'		),
		array(	'thumb_basename4'		,	'column'				,	-1								,	''								,	'thumb_basename4'		),
		array(	'caption1'				,	'column'				,	-1								,	''								,	'caption1'				),
		array(	'caption2'				,	'column'				,	-1								,	''								,	'caption2'				),
		array(	'caption3'				,	'column'				,	-1								,	''								,	'caption3'				),
		array(	'caption4'				,	'column'				,	-1								,	''								,	'caption4'				),
		array(	'column_source'			,	'column'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'column_source'			),
		array(	'status'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				),
		array(	'free'					,	'distribution'			,	XMLTEMPLATE_VAL_BOOLEAN_1_0		,	''								,	'free'					),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				),
		array(	'meta_str_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_str_datetime'		),
		array(	'meta_end_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_end_datetime'		)
	)
);

/*
CREATE TABLE sportslife_columnist (
	id SERIAL,
	PRIMARY KEY(id),
	name VARCHAR(128) NOT NULL,
	details VARCHAR(128),
	thumb_filename VARCHAR(128),
	thumb_filepath VARCHAR(128),
	thumb_basename VARCHAR(128),
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP
);
*/

$templateColumnist = array(
	'table_name' => "sportslife_columnist",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'column'				,	''						,	-1								,	''								,	''						),
		array(	'columnist'				,	'column'				,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'column'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'name'					,	'columnist'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'name'					),
		array(	'details'				,	'columnist'				,	-1								,	''								,	'details'				),
		array(	'thumb_filename'		,	'columnist'				,	-1								,	''								,	'thumb_filename'		),
		array(	'thumb_filepath'		,	'columnist'				,	-1								,	''								,	'thumb_filepath'		),
		array(	'thumb_basename'		,	'columnist'				,	-1								,	''								,	'thumb_basename'		)
	)
);

/*
CREATE TABLE sportslife_announcement (
	id SERIAL,
	PRIMARY KEY(id),
	genre_id INT NOT NULL,
	body VARCHAR(128) NOT NULL,
	url VARCHAR(256),
	free BOOLEAN NOT NULL,
	sort_num INT,
	meta_str_datetime TIMESTAMP NOT NULL,
	meta_end_datetime TIMESTAMP NOT NULL,
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP
);
*/

// Template for おしらせ announcement.php
$templateAnnouncement = array(
	'table_name' => "sportslife_announcement",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'announcement'			,	''						,	-1								,	''								,	''						),
		array(	'distribution'			,	'announcement'			,	-1								,	''								,	''						),
		array(	'disabled_flag'			,	'announcement'			,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'id'					,	'announcement'			,	XMLTEMPLATE_VAL_DB_ID			,	''								,	'id'					),
		array(	'genre_id'				,	'announcement'			,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'genre_id'				),
		array(	'body'					,	'announcement'			,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'body'					),
		array(	'url'					,	'announcement'			,	XMLTEMPLATE_VAL_URL				,	''								,	'url'					),
		array(	'device_type'			,	'announcement'			,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'device_type'			),
		array(	'free'					,	'distribution'			,	XMLTEMPLATE_VAL_BOOLEAN_1_0		,	''								,	'free'					),
		array(	'sort_num'				,	'distribution'			,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				),
		array(	'meta_str_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_str_datetime'		),
		array(	'meta_end_datetime'		,	'distribution'			,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'meta_end_datetime'		),
	)
);


/*
CREATE TABLE sportslife_pickup (
	id SERIAL,
	PRIMARY KEY(id),
	genre_id INT NOT NULL,
	related_content_id INT NOT NULL,
	sort_num INT,
	status INT NOT NULL DEFAULT '0',
	
	creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	update_date TIMESTAMP,
	disabled_flag BOOLEAN NOT NULL DEFAULT '0',
	disabled_date TIMESTAMP,
	
	FOREIGN KEY (related_content_id) REFERENCES sportslife_content(id)
); 
*/

// Template for 特集 pickup.php
$templatePickup = array(
	'table_name' => "sportslife_pickup",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'pickup'				,	''						,	-1								,	''								,	''						),
		array(	'id'					,	'pickup'				,	XMLTEMPLATE_VAL_DB_ID			,	''								,	'id'					),
		array(	'disabled_flag'			,	'pickup'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'disabled_flag'			),
		array(	'genre_id'				,	'pickup'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'genre_id'				),
		array(	'related_content_id'	,	'pickup'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'content_id'			),
		array(	'sort_num'				,	'pickup'				,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'sort_num'				),
		array(	'status'				,	'pickup'				,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'status'				)
	)
);

?>
