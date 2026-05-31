import os
import re

dirs = [
    "/Users/mizanurrahman/claude/edgecash script/affscash2",
    "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"
]

sections = [
    ("Parties", "<p>This Affiliate Agreement (“Agreement”) is entered into between AffsCash (“Company,” “we,” “us”) and the individual or entity (“Affiliate,” “you”) applying to participate in the AffsCash Affiliate Program.</p>"),
    ("Enrollment", "<p>Affiliates must submit accurate registration details. We reserve the right to approve or reject any application at our sole discretion.</p>"),
    ("Promotion Rights", "<p>Upon approval, Affiliates are granted a non-exclusive, revocable right to promote AffsCash offers using provided tracking links.</p>"),
    ("Commissions", "<ul class=\"tos-list\">\n          <li>Affiliates earn commissions for valid, non-fraudulent, verified conversions</li>\n          <li>Rates vary by offer, GEO, and advertiser requirements</li>\n          <li>Minimum payout threshold applies (e.g., $50–$100 depending on payment method)</li>\n        </ul>"),
    ("Payment Terms", "<ul class=\"tos-list\">\n          <li>Payments are issued on Net-15, Net-30, Weekly, or Upon Request basis</li>\n          <li>Payments are made via supported methods (wire, PayPal, Payoneer, etc.)</li>\n          <li>Company reserves the right to withhold payments for fraud or policy violations</li>\n        </ul>"),
    ("Prohibited Activities", "<p>Affiliates are strictly prohibited from:</p>\n        <ul class=\"tos-list\">\n          <li>Generating fake traffic or conversions</li>\n          <li>Using bots, VPN fraud, click farms, or incentivized abuse (unless approved)</li>\n          <li>Misleading advertising or brand impersonation</li>\n        </ul>"),
    ("Termination", "<p>We may suspend or terminate accounts at any time for fraud, abuse, or violation of this Agreement.</p>"),
    ("Liability", "<p>AffsCash is not responsible for lost revenue due to tracking errors, downtime, or third-party advertiser issues.</p>")
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
    filepath = os.path.join(d, "affiliate-agreement.php")
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, "r", encoding="utf-8") as f:
        file_content = f.read()
        
    # Replace Title
    file_content = re.sub(r'<title>.*?</title>', '<title>Affiliate Agreement — Affscash</title>', file_content)
    file_content = re.sub(r'<h1>.*?</h1>', '<h1>Affiliate Agreement</h1>', file_content)
    
    # Replace Notice Block
    notice_block = 'This Affiliate Program Operating Agreement (the "Agreement") is made and entered into by and between Affscash ("Affscash" or "we"), and you ("you" or "Affiliate") — the party submitting an application to become an Affscash affiliate.'
    
    file_content = re.sub(r'<div class="notice-block">.*?</div>', f'<div class="notice-block">\n      {notice_block}\n    </div>', file_content, flags=re.DOTALL)
    
    # Replace TOC
    file_content = re.sub(r'<ul class="toc-list">.*?</ul>', f'<ul class="toc-list">\n{toc_html}        </ul>', file_content, flags=re.DOTALL)
    
    # Replace Main Content (Accordions)
    # The accordions start after <div class="notice-block">...</div> and end before </main>
    # Find the start of the first section and the end of the last section
    content_pattern = r'(<section class="tos-section.*?id="section-1">.*?</section>\s*)'
    # Actually just replace everything between notice-block and </main>
    match = re.search(r'(<div class="notice-block">.*?</div>)(.*?)(</main>)', file_content, re.DOTALL)
    if match:
        new_file_content = file_content[:match.end(1)] + "\n\n" + accordions_html + match.group(3) + file_content[match.end(3):]
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(new_file_content)
        print(f"Updated {filepath}")
    else:
        print(f"Failed to match structure in {filepath}")

