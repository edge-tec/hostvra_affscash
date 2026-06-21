import os

files_to_fix = [
    'api/v2/manager/DashboardController.php',
    'api/v2/admin/DashboardController.php'
]

for file_path in files_to_fix:
    with open(file_path, 'r') as f:
        content = f.read()
    
    # In both files, inside trend/hourly sections, there are queries like:
    # SELECT HOUR(DATE_ADD(converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv, SUM(payout) as p, SUM(revenue) as r FROM conversions c LEFT JOIN clicks ck
    # SELECT HOUR(DATE_ADD(converted_at, INTERVAL ? SECOND)) as h, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
    
    content = content.replace(
        "SELECT HOUR(DATE_ADD(converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv",
        "SELECT HOUR(DATE_ADD(c.converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv"
    )
    content = content.replace(
        "SELECT HOUR(DATE_ADD(converted_at, INTERVAL ? SECOND)) as h,\n                        SUM(CASE WHEN",
        "SELECT HOUR(DATE_ADD(c.converted_at, INTERVAL ? SECOND)) as h,\n                        SUM(CASE WHEN"
    )
    content = content.replace(
        "SELECT HOUR(DATE_ADD(converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv, SUM(payout) as p, SUM(revenue) as r",
        "SELECT HOUR(DATE_ADD(c.converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv, SUM(c.payout) as p, SUM(c.revenue) as r"
    )
    
    with open(file_path, 'w') as f:
        f.write(content)
    print(f"Fixed {file_path}")
