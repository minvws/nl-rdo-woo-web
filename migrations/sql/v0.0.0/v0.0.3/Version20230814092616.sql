UPDATE document SET judgement=NULL WHERE judgement='';
UPDATE document SET judgement='public' WHERE LOWER(judgement) = 'openbaar';
UPDATE document SET judgement='partial_public' WHERE LOWER(judgement) = 'deels openbaar';
UPDATE document SET judgement='already_public' WHERE LOWER(judgement) = 'reeds openbaar';
UPDATE document SET judgement='not_public' WHERE LOWER(judgement) = 'niet openbaar';


