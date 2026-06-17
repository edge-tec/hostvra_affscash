-- Migration: 9999_fix_manager_permissions.sql
-- Description: Fix default permission for view_fraud_reports

UPDATE manager_permissions SET granted=1 WHERE permission_key='view_fraud_reports' AND granted=0;
