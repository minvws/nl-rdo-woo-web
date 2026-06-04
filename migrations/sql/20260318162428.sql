UPDATE main_document
SET language = CASE language
    WHEN 'Dutch'   THEN 'NLD'
    WHEN 'English' THEN 'ENG'
    ELSE 'NLD'
END;

UPDATE attachment
SET language = CASE language
    WHEN 'Dutch'   THEN 'NLD'
    WHEN 'English' THEN 'ENG'
    ELSE 'NLD'
END;
