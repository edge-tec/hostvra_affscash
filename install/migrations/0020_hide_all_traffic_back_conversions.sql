-- Hide all Traffic Back conversions from Manager and Affiliate views
UPDATE conversions cv
LEFT JOIN clicks ck ON cv.click_id = ck.click_id
SET cv.is_hidden = 1,
    cv.hide_reason = 'traffic_back_url'
WHERE cv.hide_reason LIKE '%traffic_back%'
   OR ck.source = 'traffic_back'
   OR EXISTS (SELECT 1 FROM traffic_back_logs tbl WHERE tbl.click_id = cv.click_id);
