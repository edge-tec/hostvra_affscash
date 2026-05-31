import os
import re

dirs = [
    "/Users/mizanurrahman/claude/edgecash script/affscash2",
    "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"
]

sections = [
    ("General Dashboard Disclaimer", "<p>All information displayed in the AffsCash dashboard is provided for informational and tracking purposes only.</p>\n<p>The dashboard may include:</p>\n<ul class=\"tos-list\">\n  <li>Estimated earnings</li>\n  <li>Click and conversion data</li>\n  <li>Pending and approved statistics</li>\n  <li>Performance analytics</li>\n</ul>\n<p>This data is not final until confirmed by advertiser validation and compliance review.</p>"),
    
    ("Earnings Disclaimer", "<p>Earnings shown in the dashboard are estimated and not guaranteed income.</p>\n<p>Final payouts may vary due to:</p>\n<ul class=\"tos-list\">\n  <li>Advertiser approval or rejection</li>\n  <li>Fraud detection results</li>\n  <li>Chargebacks or reversals</li>\n  <li>Tracking adjustments or corrections</li>\n</ul>\n<p>AffsCash is not responsible for differences between estimated and final earnings.</p>"),
    
    ("Data Accuracy Disclaimer", "<p>While we use advanced tracking systems, we do not guarantee:</p>\n<ul class=\"tos-list\">\n  <li>100% accurate tracking</li>\n  <li>Real-time synchronization of all conversions</li>\n  <li>Absence of reporting delays or technical errors</li>\n</ul>\n<p>Some data may be:</p>\n<ul class=\"tos-list\">\n  <li>Delayed</li>\n  <li>Updated after validation</li>\n  <li>Adjusted during compliance review</li>\n</ul>"),
    
    ("Pending Conversions Disclaimer", "<p>All pending conversions are:</p>\n<ul class=\"tos-list\">\n  <li>Not confirmed earnings</li>\n  <li>Subject to advertiser approval</li>\n  <li>May be approved, rejected, or reversed at any time</li>\n</ul>\n<p>Pending status does not guarantee payment.</p>"),
    
    ("Fraud & Invalid Activity Disclaimer", "<p>Any traffic identified as fraudulent or invalid may result in:</p>\n<ul class=\"tos-list\">\n  <li>Removal of earnings</li>\n  <li>Reversal of conversions</li>\n  <li>Account suspension or termination</li>\n</ul>\n<p>This includes:</p>\n<ul class=\"tos-list\">\n  <li>Bot traffic</li>\n  <li>VPN/proxy abuse</li>\n  <li>Self-conversions</li>\n  <li>Incentivized or misleading traffic (without approval)</li>\n</ul>"),
    
    ("Payment Disclaimer", "<p>Payment data shown in the dashboard is indicative only.</p>\n<p>Actual payments depend on:</p>\n<ul class=\"tos-list\">\n  <li>Minimum payout thresholds</li>\n  <li>Fraud clearance</li>\n  <li>Advertiser settlement cycles</li>\n  <li>Compliance verification</li>\n</ul>\n<p>AffsCash reserves the right to delay or withhold payments when necessary.</p>"),
    
    ("Performance Metrics Disclaimer", "<p>All performance statistics (CTR, EPC, CR, etc.) are:</p>\n<ul class=\"tos-list\">\n  <li>Estimates only</li>\n  <li>For analytical use</li>\n  <li>Not guarantees of future performance</li>\n</ul>\n<p>Performance may change due to traffic quality and advertiser conditions.</p>"),
    
    ("Security Disclaimer", "<p>We implement strong security measures; however:</p>\n<ul class=\"tos-list\">\n  <li>No system is fully secure</li>\n  <li>Users are responsible for protecting their login credentials</li>\n</ul>\n<p>AffsCash is not liable for unauthorized access due to user negligence.</p>"),
    
    ("No Financial Guarantee", "<p>AffsCash does not guarantee:</p>\n<ul class=\"tos-list\">\n  <li>Earnings</li>\n  <li>Conversion rates</li>\n  <li>Traffic performance</li>\n  <li>Advertiser acceptance</li>\n</ul>\n<p>Affiliate marketing results vary based on multiple external factors.</p>"),
    
    ("Platform Rights Disclaimer", "<p>AffsCash reserves the right to:</p>\n<ul class=\"tos-list\">\n  <li>Modify dashboard data</li>\n  <li>Adjust or reverse earnings</li>\n  <li>Suspend or restrict accounts</li>\n  <li>Update tracking systems without notice</li>\n</ul>\n<p>All decisions made by the compliance team are final.</p>"),
    
    ("Service Availability Disclaimer", "<p>We do not guarantee:</p>\n<ul class=\"tos-list\">\n  <li>Continuous dashboard access</li>\n  <li>Error-free performance</li>\n  <li>Uninterrupted tracking services</li>\n</ul>\n<p>Temporary downtime or delays may occur.</p>"),
    
    ("Acceptance of Disclaimers", "<p>By using the AffsCash Dashboard, you agree that:</p>\n<ul class=\"tos-list\">\n  <li>You understand these disclaimers</li>\n  <li>You accept all risks related to affiliate marketing</li>\n  <li>You acknowledge that all earnings are performance-based and subject to validation</li>\n</ul>")
]

# Create TOC and Accordions
toc_html = ""
accordions_html = ""

for i, (title, content) in enumerate(sections, 1):
    num_str = f"{i:02d}"
    
    # TOC
    toc_html += f'          <li><a href="#section-{i}"><span class="toc-num">{num_str}</span> <span class="toc-text">{title}</span></a></li>\n'
    
    # Accordion
    is_open = ' is-open' if i == 1 else ''
    accordions_html += f"""    <!-- Section {i} -->
    <section class="tos-section{is_open}" id="section-{i}">
      <div class="accordion-header">
        <span class="section-number">{num_str}</span>
        <h2>{title}</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        {content}
      </div>
    </section>

"""

for d in dirs:
    filepath = os.path.join(d, "dashboard-disclaimers.php")
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, "r", encoding="utf-8") as f:
        file_content = f.read()
        
    # Replace Title
    file_content = re.sub(r'<title>.*?</title>', '<title>Dashboard Disclaimers — Affscash</title>', file_content)
    file_content = re.sub(r'<h1>.*?</h1>', '<h1>Dashboard Disclaimers</h1>', file_content)
    
    # Replace Notice Block
    notice_block = '<strong>Last Updated: June 1, 2026</strong><br>Applies To: All users of the AffsCash Affiliate Dashboard.'
    
    file_content = re.sub(r'<div class="notice-block">.*?</div>', f'<div class="notice-block">\n      {notice_block}\n    </div>', file_content, flags=re.DOTALL)
    
    # Replace TOC
    file_content = re.sub(r'<ul class="toc-list">.*?</ul>', f'<ul class="toc-list">\n{toc_html}        </ul>', file_content, flags=re.DOTALL)
    
    # Replace Main Content (Accordions)
    match = re.search(r'(<div class="notice-block">.*?</div>)(.*?)(</main>)', file_content, re.DOTALL)
    if match:
        new_file_content = file_content[:match.end(1)] + "\n\n" + accordions_html + match.group(3) + file_content[match.end(3):]
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(new_file_content)
        print(f"Updated {filepath}")
    else:
        print(f"Failed to match structure in {filepath}")

