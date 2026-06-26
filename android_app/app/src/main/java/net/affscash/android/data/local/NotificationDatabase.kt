package net.affscash.android.data.local

import android.content.Context
import androidx.room.Dao
import androidx.room.Database
import androidx.room.Entity
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.PrimaryKey
import androidx.room.Query
import androidx.room.Room
import androidx.room.RoomDatabase
import kotlinx.coroutines.flow.Flow

/**
 * Local notification entity stored in Room database.
 * Mirrors the server-side notification structure.
 */
@Entity(tableName = "notifications")
data class LocalNotification(
    @PrimaryKey val id: Int,
    val title: String,
    val message: String,
    val type: String = "info",
    val link: String? = null,
    val notificationType: String? = null,
    val deepLinkRoute: String? = null,
    val isRead: Int = 0,
    val createdAt: String,
    /** When this notification was received locally via push */
    val receivedAt: Long = System.currentTimeMillis()
)

/**
 * DAO for notification database operations.
 */
@Dao
interface NotificationDao {

    @Query("SELECT * FROM notifications ORDER BY createdAt DESC")
    fun getAllNotifications(): Flow<List<LocalNotification>>

    @Query("SELECT * FROM notifications ORDER BY createdAt DESC LIMIT :limit OFFSET :offset")
    suspend fun getNotificationsPaged(limit: Int, offset: Int): List<LocalNotification>

    @Query("SELECT COUNT(*) FROM notifications WHERE isRead = 0")
    fun getUnreadCountFlow(): Flow<Int>

    @Query("SELECT COUNT(*) FROM notifications WHERE isRead = 0")
    suspend fun getUnreadCount(): Int

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(notification: LocalNotification)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(notifications: List<LocalNotification>)

    @Query("UPDATE notifications SET isRead = 1 WHERE id = :id")
    suspend fun markAsRead(id: Int)

    @Query("UPDATE notifications SET isRead = 1")
    suspend fun markAllAsRead()

    @Query("DELETE FROM notifications WHERE id = :id")
    suspend fun delete(id: Int)

    @Query("DELETE FROM notifications")
    suspend fun deleteAll()

    @Query("SELECT EXISTS(SELECT 1 FROM notifications WHERE id = :id)")
    suspend fun exists(id: Int): Boolean
}

/**
 * Room database for local notification caching.
 * Enables offline notification viewing and instant badge counts.
 */
@Database(entities = [LocalNotification::class], version = 1, exportSchema = false)
abstract class NotificationDatabase : RoomDatabase() {
    abstract fun notificationDao(): NotificationDao

    companion object {
        private const val DB_NAME = "affscash_notifications.db"

        @Volatile
        private var INSTANCE: NotificationDatabase? = null

        fun getInstance(context: Context): NotificationDatabase {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: Room.databaseBuilder(
                    context.applicationContext,
                    NotificationDatabase::class.java,
                    DB_NAME
                )
                    .fallbackToDestructiveMigration()
                    .build()
                    .also { INSTANCE = it }
            }
        }
    }
}
