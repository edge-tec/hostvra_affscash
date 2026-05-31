import os
import re

dir_path = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"

sections = [
    ("Overview", "<p>AffsCash.net operates as a performance-based affiliate (CPA/CPI/CPR) network. All commissions and payments are based strictly on verified and approved conversions from advertisers.</p>\n<p>This policy explains how payments are processed and when adjustments, reversals, or withholdings may occur.</p>"),
    
    ("Affiliate Earnings", "<p>Affiliate earnings are:</p>\n<ul class=\"tos-list\">\n  <li>Based on approved conversions only</li>\n  <li>Subject to advertiser validation</li>\n  <li>Not guaranteed until confirmed in final payout</li>\n</ul>\n<p>Displayed dashboard earnings are estimated and may change.</p>"),
    
    ("Payment Processing", "<p>Payments are processed based on one of the following schedules:</p>\n<ul class=\"tos-list\">\n  <li>Weekly</li>\n  <li>Bi-weekly</li>\n  <li>Net-15 / Net-30</li>\n  <li>Upon request (if approved)</li>\n</ul>\n<p>Processing time may vary depending on:</p>\n<ul class=\"tos-list\">\n  <li>Payment method</li>\n  <li>Affiliate performance history</li>\n  <li>Compliance checks</li>\n  <li>Advertiser settlement timing</li>\n</ul>"),
    
    ("Minimum Payout Threshold", "<p>A minimum payout threshold applies (varies by payment method), typically:</p>\n<ul class=\"tos-list\">\n  <li>$50 – $100 minimum balance required</li>\n</ul>\n<p>Affiliates must reach this threshold before requesting payment.</p>"),
    
    ("Non-Payable Traffic", "<p>AffsCash does not pay for:</p>\n<ul class=\"tos-list\">\n  <li>Fraudulent traffic</li>\n  <li>Bot or automated clicks</li>\n  <li>Incentivized traffic (unless explicitly approved)</li>\n  <li>Self-generated conversions</li>\n  <li>Duplicate or invalid leads</li>\n  <li>VPN/proxy manipulated traffic</li>\n</ul>\n<p>Any such activity will result in removal of earnings.</p>"),
    
    ("Payment Adjustments & Reversals", "<p>AffsCash reserves the right to adjust or reverse payments in cases of:</p>\n<ul class=\"tos-list\">\n  <li>Advertiser chargebacks</li>\n  <li>Fraud detection results</li>\n  <li>Invalid or duplicate conversions</li>\n  <li>Tracking discrepancies</li>\n  <li>Policy violations</li>\n</ul>\n<p>These adjustments are standard in CPA marketing and reflect final advertiser decisions.</p>"),
    
    ("Pending Earnings Disclaimer", "<p>Pending earnings shown in the dashboard:</p>\n<ul class=\"tos-list\">\n  <li>Are not guaranteed payments</li>\n  <li>May be approved, rejected, or reversed</li>\n  <li>Are subject to advertiser validation and fraud review</li>\n</ul>\n<p>Only approved conversions are eligible for payout.</p>"),
    
    ("Payment Holds", "<p>AffsCash may place a temporary hold on payments if:</p>\n<ul class=\"tos-list\">\n  <li>Fraud risk is detected</li>\n  <li>Traffic quality requires review</li>\n  <li>Account is under compliance investigation</li>\n  <li>Advertiser disputes arise</li>\n</ul>\n<p>Payment holds are used to protect the integrity of the network.</p>"),
    
    ("Refund Policy (Affiliate Earnings)", "<p>Due to the nature of affiliate marketing:</p>\n<ul class=\"tos-list\">\n  <li>Affiliate earnings are non-refundable once paid</li>\n  <li>No refunds are issued for rejected or reversed conversions</li>\n  <li>No compensation is provided for traffic losses or account restrictions</li>\n</ul>"),
    
    ("Chargebacks", "<p>If an advertiser issues a chargeback:</p>\n<ul class=\"tos-list\">\n  <li>The corresponding affiliate commission will be deducted</li>\n  <li>Future earnings may be adjusted accordingly</li>\n  <li>Affiliates are not directly charged; only earnings are affected</li>\n</ul>"),
    
    ("International Payments", "<p>AffsCash supports global affiliates. However:</p>\n<ul class=\"tos-list\">\n  <li>Payment methods and processing times may vary by country</li>\n  <li>Currency conversion fees may apply</li>\n  <li>Local banking regulations may affect transfers</li>\n</ul>"),
    
    ("Compliance Requirement", "<p>To receive payments, affiliates must:</p>\n<ul class=\"tos-list\">\n  <li>Comply with all AffsCash policies</li>\n  <li>Pass fraud and identity verification checks (if required)</li>\n  <li>Provide valid payment details</li>\n</ul>\n<p>Failure to comply may delay or cancel payments.</p>"),
    
    ("Final Decision Authority", "<p>AffsCash reserves the right to:</p>\n<ul class=\"tos-list\">\n  <li>Approve or reject payments</li>\n  <li>Reverse earnings after review</li>\n  <li>Modify payment terms at any time</li>\n  <li>Make final decisions on disputes</li>\n</ul>\n<p>All compliance decisions are final and binding.</p>"),
    
    ("Acceptance of Policy", "<p>By using AffsCash.net, you agree that:</p>\n<ul class=\"tos-list\">\n  <li>You understand this Refund & Payment Policy</li>\n  <li>You accept performance-based earning conditions</li>\n  <li>You acknowledge that payments depend on advertiser validation</li>\n</ul>")
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

filepath = os.path.join(dir_path, "refund-payment-policy.php")
if os.path.exists(filepath):
    with open(filepath, "r", encoding="utf-8") as f:
        file_content = f.read()
        
    # Replace Title
    file_content = re.sub(r'<title>.*?</title>', '<title>Refund & Payment Policy — Affscash</title>', file_content)
    file_content = re.sub(r'<h1>.*?</h1>', '<h1>Refund & Payment Policy</h1>', file_content)
    
    # Replace Notice Block
    notice_block = '<strong>Last Updated: June 1, 2026</strong><br>Applies To: All Affiliates, Publishers, and Partners of AffsCash.net'
    
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

