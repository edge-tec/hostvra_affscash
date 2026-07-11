<?php
$appName  = Helpers::e(Config::get('config','app.name') ?? 'Affscash');
$appLogo  = Config::get('config','app.logo');
$logoSrc  = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin = Auth::id() && Auth::role() === 'admin';
$_isLogged= (bool)Auth::id();
$_role    = Auth::role();
$_currentPage = 'reviews';

// Filters
$ratingFilter = (int)($_GET['rating'] ?? 0);
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

$reviews   = [];
$totalCount= 0;

try {
    // Auto-create table
    Database::query("CREATE TABLE IF NOT EXISTS `landing_reviews` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name`        VARCHAR(200) NOT NULL,
        `email`       VARCHAR(255) DEFAULT NULL,
        `role_title`  VARCHAR(200) DEFAULT NULL,
        `avatar`      VARCHAR(512) DEFAULT NULL,
        `rating`      TINYINT UNSIGNED DEFAULT 5,
        `review_text` TEXT NOT NULL,
        `country`     VARCHAR(100) DEFAULT NULL,
        `is_featured` TINYINT(1) DEFAULT 0,
        `sort_order`  INT DEFAULT 0,
        `status`      ENUM('pending','active','inactive') DEFAULT 'active',
        `source`      ENUM('admin','public') DEFAULT 'admin',
        `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_sort`   (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $where  = "status='active'";
    $params = [];
    if ($ratingFilter >= 1 && $ratingFilter <= 5) {
        $where .= " AND rating=?";
        $params[] = $ratingFilter;
    }

    $countRow   = Database::fetchOne("SELECT COUNT(*) as c FROM landing_reviews WHERE $where", $params);
    $totalCount = (int)($countRow['c'] ?? 0);
    $totalPages = max(1, (int)ceil($totalCount / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    $reviews = Database::fetchAll(
        "SELECT name, role_title, avatar, rating, review_text, country, is_featured, source, created_at
         FROM landing_reviews WHERE $where
         ORDER BY is_featured DESC, sort_order ASC, id DESC
         LIMIT $perPage OFFSET $offset",
        $params
    );
} catch (\Throwable $e) {}

$pageTitle = 'Reviews | ' . $appName;
$seoDescription = 'Read reviews and testimonials from our top affiliates and see why ' . $appName . ' is the leading CPA Affiliate Network.';
require BASE_PATH . '/views/layouts/public_top.php';
?>

<!-- PAGE BANNER -->
<div class="page-banner">
  <div class="container">
    <div class="breadcrumb-bar">
      <a href="/">Home</a> <span>/</span> <span>Reviews</span>
    </div>
    <h1>⭐ Affiliate <em style="-webkit-text-fill-color:#fff;background:none;color:#fff">Reviews</em></h1>
    <p>Real experiences from affiliates worldwide. See what publishers say about <?= $appName ?>.</p>
  </div>
</div>

<!-- CONTENT -->
<section style="padding:64px 0;background:#fff">
  <div class="container">

    <!-- Rating filter -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:36px;justify-content:center">
      <a href="/reviews" style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:50px;font-size:13px;font-weight:600;border:2px solid <?= $ratingFilter===0?'var(--violet)':'var(--border)' ?>;background:<?= $ratingFilter===0?'var(--violet)':'#fff' ?>;color:<?= $ratingFilter===0?'#fff':'var(--text)' ?>;text-decoration:none;transition:all .2s">
        All Reviews <span style="background:<?= $ratingFilter===0?'rgba(255,255,255,.25)':'var(--bg2)' ?>;border-radius:10px;padding:1px 8px;font-size:11px"><?= $totalCount ?: '' ?></span>
      </a>
      <?php for ($i = 5; $i >= 1; $i--): ?>
      <a href="/reviews?rating=<?= $i ?>" style="display:inline-flex;align-items:center;gap:5px;padding:9px 18px;border-radius:50px;font-size:13px;font-weight:600;border:2px solid <?= $ratingFilter===$i?'#F59E0B':'var(--border)' ?>;background:<?= $ratingFilter===$i?'#FEF3C7':'#fff' ?>;color:<?= $ratingFilter===$i?'#92400E':'var(--text)' ?>;text-decoration:none;transition:all .2s">
        <?= str_repeat('★', $i) ?> <span style="font-size:12px"><?= $i ?> Star<?= $i>1?'s':'' ?></span>
      </a>
      <?php endfor; ?>
    </div>

    <?php if (!empty($reviews)): ?>

    <!-- Stats bar -->
    <div style="background:var(--grad-section-a);border-radius:14px;padding:18px 28px;margin-bottom:36px;display:flex;align-items:center;gap:32px;flex-wrap:wrap;justify-content:center">
      <div style="text-align:center">
        <div style="font-size:28px;font-weight:800;font-family:'Rajdhani',sans-serif;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent"><?= $totalCount ?></div>
        <div style="font-size:12px;color:var(--muted);font-weight:600">Total Reviews</div>
      </div>
      <div style="width:1px;height:40px;background:var(--border)"></div>
      <div style="text-align:center">
        <div style="font-size:28px;font-weight:800;font-family:'Rajdhani',sans-serif;color:#F59E0B">★★★★★</div>
        <div style="font-size:12px;color:var(--muted);font-weight:600">Verified Affiliates</div>
      </div>
      <div style="width:1px;height:40px;background:var(--border)"></div>
      <div style="text-align:center">
        <div style="font-size:28px;font-weight:800;font-family:'Rajdhani',sans-serif;color:var(--green)">100%</div>
        <div style="font-size:12px;color:var(--muted);font-weight:600">Real Experiences</div>
      </div>
    </div>

    <!-- Reviews grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px;margin-bottom:48px">
      <?php foreach ($reviews as $rv): ?>
      <div style="background:#fff;border-radius:18px;padding:26px;box-shadow:0 4px 24px rgba(124,58,237,.07);border:1px solid var(--border);display:flex;flex-direction:column;gap:14px;transition:transform .2s,box-shadow .2s" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 36px rgba(124,58,237,.13)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 24px rgba(124,58,237,.07)'">
        <!-- Stars + badges -->
        <div style="display:flex;align-items:center;justify-content:space-between">
          <span style="color:#F59E0B;font-size:18px;letter-spacing:2px"><?= str_repeat('★',(int)$rv['rating']) ?><?= str_repeat('☆',5-(int)$rv['rating']) ?></span>
          <?php if ($rv['is_featured']): ?>
          <span style="background:#FEF3C7;color:#92400E;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700">⭐ Featured</span>
          <?php elseif (($rv['source']??'admin')==='public'): ?>
          <span style="background:#EFF6FF;color:#2563EB;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600">✓ Verified</span>
          <?php endif; ?>
        </div>
        <!-- Review text -->
        <p style="font-size:14px;color:#374151;line-height:1.75;flex:1;font-style:italic">"<?= Helpers::e($rv['review_text']) ?>"</p>
        <!-- Author -->
        <div style="display:flex;align-items:center;gap:12px;padding-top:14px;border-top:1px solid var(--border)">
          <?php if ($rv['avatar']): ?>
          <img src="<?= Helpers::e($rv['avatar']) ?>" alt="" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
          <?php else: ?>
          <div style="width:46px;height:46px;border-radius:50%;background:var(--grad-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;flex-shrink:0">
            <?= strtoupper(substr(htmlspecialchars($rv['name'],ENT_QUOTES,'UTF-8'),0,1)) ?>
          </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:700;font-size:14px;color:var(--text)"><?= Helpers::e($rv['name']) ?></div>
            <?php if ($rv['role_title']): ?><div style="font-size:12px;color:var(--muted)"><?= Helpers::e($rv['role_title']) ?></div><?php endif; ?>
            <?php if ($rv['country']): ?><div style="font-size:11px;color:var(--muted)">📍 <?= Helpers::e($rv['country']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-bottom:48px;flex-wrap:wrap">
      <?php if ($page > 1): ?>
      <a href="/reviews?<?= $ratingFilter?'rating='.$ratingFilter.'&':'' ?>page=<?= $page-1 ?>" style="padding:8px 16px;border-radius:8px;border:1.5px solid var(--border);color:var(--text);font-size:13px;font-weight:600;text-decoration:none;transition:all .2s" onmouseover="this.style.borderColor='var(--violet)';this.style.color='var(--violet)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
      <a href="/reviews?<?= $ratingFilter?'rating='.$ratingFilter.'&':'' ?>page=<?= $i ?>" style="padding:8px 16px;border-radius:8px;border:1.5px solid <?= $i===$page?'var(--violet)':'var(--border)' ?>;background:<?= $i===$page?'var(--violet)':'#fff' ?>;color:<?= $i===$page?'#fff':'var(--text)' ?>;font-size:13px;font-weight:600;text-decoration:none"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="/reviews?<?= $ratingFilter?'rating='.$ratingFilter.'&':'' ?>page=<?= $page+1 ?>" style="padding:8px 16px;border-radius:8px;border:1.5px solid var(--border);color:var(--text);font-size:13px;font-weight:600;text-decoration:none;transition:all .2s" onmouseover="this.style.borderColor='var(--violet)';this.style.color='var(--violet)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty state -->
    <div style="text-align:center;padding:64px 24px;color:var(--muted)">
      <div style="font-size:48px;margin-bottom:16px;opacity:.4">⭐</div>
      <h3 style="font-size:20px;color:var(--muted);margin-bottom:8px">No reviews yet</h3>
      <p style="font-size:14px">Be the first to share your experience with <?= $appName ?>!</p>
    </div>
    <?php endif; ?>

    <!-- Submit a review form -->
    <div id="write-review" style="background:var(--grad-section-a);border-radius:24px;padding:48px 40px;max-width:760px;margin:0 auto;border:1px solid var(--border)">
      <div style="text-align:center;margin-bottom:32px">
        <div class="eyebrow" style="justify-content:center"><span class="pulse"></span> Share Your Experience</div>
        <h2 style="font-size:30px;margin-bottom:10px">Write a <em>Review</em></h2>
        <p style="font-size:14px;color:var(--muted)">Your review will appear after admin approval. Thank you for your feedback!</p>
      </div>
      <form id="reviewForm" onsubmit="submitReview(event)" novalidate>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label style="font-size:13px;font-weight:600;color:var(--text);display:block;margin-bottom:6px">Your Name <span style="color:var(--pink)">*</span></label>
            <input type="text" id="rv_name" class="form-control-custom" placeholder="John Smith" required maxlength="100">
          </div>
          <div>
            <label style="font-size:13px;font-weight:600;color:var(--text);display:block;margin-bottom:6px">Email <span style="color:var(--muted);font-weight:400">(private)</span></label>
            <input type="email" id="rv_email" class="form-control-custom" placeholder="you@example.com">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label style="font-size:13px;font-weight:600;color:var(--text);display:block;margin-bottom:6px">Role / Title</label>
            <input type="text" id="rv_role" class="form-control-custom" placeholder="Affiliate Marketer">
          </div>
          <div>
            <label style="font-size:13px;font-weight:600;color:var(--text);display:block;margin-bottom:6px">Country</label>
            <input type="text" id="rv_country" class="form-control-custom" placeholder="United States">
          </div>
        </div>
        <!-- Star rating picker -->
        <div style="margin-bottom:16px">
          <label style="font-size:13px;font-weight:600;color:var(--text);display:block;margin-bottom:8px">Your Rating <span style="color:var(--pink)">*</span></label>
          <div id="starPicker" style="display:flex;gap:8px;font-size:32px;cursor:pointer">
            <?php for($si=1;$si<=5;$si++): ?>
            <span data-val="<?= $si ?>" onclick="setRating(<?= $si ?>)" onmouseover="hoverRating(<?= $si ?>)" onmouseout="resetRatingHover()"
                  style="color:#E2E8F0;transition:color .12s;user-select:none;line-height:1">★</span>
            <?php endfor; ?>
          </div>
          <input type="hidden" id="rv_rating" value="5">
        </div>
        <!-- Honeypot -->
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
        <div style="margin-bottom:20px">
          <label style="font-size:13px;font-weight:600;color:var(--text);display:block;margin-bottom:6px">Your Review <span style="color:var(--pink)">*</span></label>
          <textarea id="rv_text" class="form-control-custom" rows="5" maxlength="1000" required
                    placeholder="Share your experience with <?= $appName ?>… (min 10 characters)" style="resize:vertical"></textarea>
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Min 10 · Max 1000 characters</div>
        </div>
        <button type="submit" id="rvSubmitBtn" class="btn-primary-custom" style="width:100%;justify-content:center;font-size:15px;padding:14px">
          <i class="fa-solid fa-paper-plane"></i> Submit My Review
        </button>
        <div id="rvSuccess" style="display:none;margin-top:16px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.25);border-radius:12px;padding:16px;text-align:center;color:var(--green)">
          ✅ Thank you! Your review has been submitted and is pending admin approval.
        </div>
        <div id="rvError" style="display:none;margin-top:16px;background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.22);border-radius:12px;padding:16px;text-align:center;color:#dc2626">
          ❌ <span id="rvErrorText">Something went wrong. Please try again.</span>
        </div>
      </form>
    </div>

  </div>
</section>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>

<script>
var _rvRating = 5;
function setRating(v){_rvRating=v;document.getElementById('rv_rating').value=v;updateStars(v);}
function hoverRating(v){updateStars(v);}
function resetRatingHover(){updateStars(_rvRating);}
function updateStars(v){
  document.querySelectorAll('#starPicker span').forEach(function(s,i){
    s.style.color = i < v ? '#F59E0B' : '#E2E8F0';
  });
}
updateStars(5);
function submitReview(e){
  e.preventDefault();
  var btn=document.getElementById('rvSubmitBtn');
  var name=document.getElementById('rv_name').value.trim();
  var text=document.getElementById('rv_text').value.trim();
  if(!name||name.length<2){alert('Please enter your name (min 2 characters).');return;}
  if(!text||text.length<10){alert('Please write a review (min 10 characters).');return;}
  btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
  var data=new FormData();
  data.append('name',name);
  data.append('email',document.getElementById('rv_email').value.trim());
  data.append('role_title',document.getElementById('rv_role').value.trim());
  data.append('country',document.getElementById('rv_country').value.trim());
  data.append('rating',document.getElementById('rv_rating').value);
  data.append('review_text',text);
  data.append('website','');
  fetch('/api/submit_review.php',{method:'POST',body:data})
    .then(function(r){return r.json();})
    .then(function(j){
      if(j.success){
        document.getElementById('rvSuccess').style.display='block';
        document.getElementById('rvError').style.display='none';
        document.getElementById('reviewForm').reset();
        updateStars(5);_rvRating=5;
        btn.innerHTML='✅ Submitted!';
      } else {
        document.getElementById('rvErrorText').textContent=j.error||'Something went wrong.';
        document.getElementById('rvError').style.display='block';
        document.getElementById('rvSuccess').style.display='none';
        btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Submit My Review';
      }
    })
    .catch(function(){
      document.getElementById('rvErrorText').textContent='Network error. Please try again.';
      document.getElementById('rvError').style.display='block';
      btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Submit My Review';
    });
}
</script>
