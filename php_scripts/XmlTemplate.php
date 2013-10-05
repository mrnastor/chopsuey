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


$templateContent = array(
	'table_name' => "content",
	'tags' => array(
				//Tag Name(Element 0)		Parent Tag(Element 1)		Validation (Element 2)				Attribute(Element 3)				DB Field(Element 4)
		array(	'content'				,	''						,	-1								,	''								,	''						),
		array(	'tag1'	 				,	'content'				,	-1								,	''								,	''						),
		array(	'tag2'					,	'content'				,	XMLTEMPLATE_VAL_DB_ID			,	''								,	'db_field_1'			),
		array(	'tag3'					,	'content'				,	XMLTEMPLATE_VAL_YES_NO			,	''								,	'db_field_2'			),
		array(	'tag4'					,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'db_field_3'			),
		array(	'tag5'					,	'content'				,	-1								,	''								,	'db_field_4'			),
		array(	'tag6'					,	'content'				,	XMLTEMPLATE_VAL_INT_NOT_NULL	,	''								,	'db_field_5'			),
		array(	'tag7'					,	'content'				,	XMLTEMPLATE_VAL_TSTAMP_CAN_NULL	,	''								,	'db_field_6'			),
		array(	'tag8'					,	'content'				,	XMLTEMPLATE_VAL_STRING_NOT_NULL	,	''								,	'db_field_7'			),
		array(	'tag9'					,	'tag10'					,	XMLTEMPLATE_VAL_INT_CAN_NULL	,	''								,	'db_field_8'			),
		array(	'tag11'					,	'tag10'					,	XMLTEMPLATE_VAL_BOOLEAN_1_0		,	''								,	'db_field_9'			),
		array(	'tag12'					,	'tag10'					,	XMLTEMPLATE_VAL_TSTAMP_NOT_NULL	,	''								,	'db_field_10'			)
	)
);



?>
