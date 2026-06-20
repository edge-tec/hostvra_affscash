-- Migration: 0020_add_platform_source.sql
-- Description: Add platform_source to user activity tables

ALTER TABLE user_login_logs ADD COLUMN platform_source VARCHAR(30) DEFAULT 'Web' AFTER os;
ALTER TABLE user_active_sessions ADD COLUMN platform_source VARCHAR(30) DEFAULT 'Web' AFTER os;
