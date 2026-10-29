<?php
require 'db.php';
require 'header.php';
?>

<div style="max-width: 800px; margin: 0 auto; background: var(--container-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
    <h2>Frequently Asked Questions (FAQ)</h2>
    
    <h3>1. What are your rental policies?</h3>
    <p>We allow rentals for a minimum of 1 day. The return date must be strictly after the delivery date. You cannot select past dates for booking.</p>

    <h3>2. What is your damage policy?</h3>
    <p>Every hardware unit has a unique serial number that is tracked. If the unit is returned damaged, a fine will be calculated based on the condition. Major damages may result in replacement cost charges.</p>

    <h3>3. What are the delivery expectations?</h3>
    <p>Once your order is Confirmed, our team will dispatch the item to your delivery address and city. You can track the status in your Customer Dashboard. Orders will be marked as 'Delivered' upon handover.</p>

    <h3>4. Can I cancel my order?</h3>
    <p>Yes, you can cancel your order from your dashboard as long as the status is marked as "Pending" or "Confirmed" before shipping.</p>
</div>

<?php require 'footer.php'; ?>
