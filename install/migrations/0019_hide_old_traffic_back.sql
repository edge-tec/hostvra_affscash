UPDATE conversions c
JOIN clicks ck ON c.click_id = ck.click_id
SET c.is_hidden = 1,
    c.hide_reason = 'traffic_back_url'
WHERE ck.source = 'traffic_back' 
  AND c.is_hidden = 0;
