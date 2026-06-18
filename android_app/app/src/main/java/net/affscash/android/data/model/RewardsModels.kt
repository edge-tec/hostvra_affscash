package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class RewardRule(
    val id: String,
    val title: String,
    val description: String? = null,
    @SerialName("threshold_usd") val thresholdUsd: Double,
    @SerialName("image_path") val imagePath: String? = null,
    @SerialName("badge_label") val badgeLabel: String? = null,
    @SerialName("badge_color") val badgeColor: String? = null,
    @SerialName("expires_at") val expiresAt: String? = null,
    @SerialName("is_unlocked") val isUnlocked: Boolean = false
)

@Serializable
data class RewardGrant(
    val id: String,
    @SerialName("rule_id") val ruleId: String,
    val status: String,
    @SerialName("granted_at") val grantedAt: String
)

@Serializable
data class NextMilestone(
    val rule: RewardRule? = null,
    val earned: Double = 0.0
)

@Serializable
data class RewardsResponse(
    val success: Boolean,
    @SerialName("next_milestone") val nextMilestone: NextMilestone? = null,
    @SerialName("available_rewards") val availableRewards: List<RewardRule> = emptyList(),
    @SerialName("earned_rewards") val earnedRewards: List<RewardGrant> = emptyList(),
    val error: String? = null
)
