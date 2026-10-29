<?php
require 'db.php';
if (!isAdmin()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $gadget_id = (int)$_POST['gadget_id'];
        $serial_number = mysqli_real_escape_string($conn, trim($_POST['serial_number']));
        
        $res = mysqli_query($conn, "INSERT INTO hardware_units (gadget_id, serial_number, status) VALUES ($gadget_id, '$serial_number', 'Available')");
        if (!$res) {
            $error = "Failed to add unit. Serial number must be unique.";
        }
    }
    elseif (isset($_POST['update_status'])) {
        $unit_id = (int)$_POST['unit_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        mysqli_query($conn, "UPDATE hardware_units SET status = '$status' WHERE id = $unit_id");
    }
    elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['unit_id'];
        mysqli_query($conn, "DELETE FROM hardware_units WHERE id = $id");
    }
    if (!isset($error)) redirect('admin_hardware.php');
}

$res = mysqli_query($conn, "SELECT h.*, g.title FROM hardware_units h JOIN gadgets g ON h.gadget_id = g.id");
$units = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $units[] = $row;
    }
}

$res_gadgets = mysqli_query($conn, "SELECT id, title FROM gadgets");
$gadgets = [];
if ($res_gadgets) {
    while ($row = mysqli_fetch_assoc($res_gadgets)) {
        $gadgets[] = $row;
    }
}

require 'header.php';
?>

<h2>Manage Hardware Units</h2>
<?php if(isset($error)): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>

<div style="background: var(--container-bg); padding:30px; border:1px solid var(--border-color); margin-bottom: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <h3>Add New Hardware Unit</h3>
    <form method="POST" style="max-width: 500px;">
        <label>Gadget:</label>
        <select name="gadget_id" required>
            <?php foreach ($gadgets as $g): ?>
                <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <label>Serial Number:</label>
        <input type="text" name="serial_number" required>
        <button type="submit" name="add" class="btn">Add Unit</button>
    </form>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Gadget</th>
        <th>Serial Number</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php foreach ($units as $u): ?>
    <tr>
        <td><?php echo $u['id']; ?></td>
        <td>
            <a href="gadget_details.php?id=<?php echo $u['gadget_id']; ?>" target="_blank" style="color: inherit; text-decoration: none; font-weight: 600;" onmouseover="this.style.color='#00d2ff'" onmouseout="this.style.color='inherit'" title="View product page">
                <?php echo htmlspecialchars($u['title']); ?> &#8599;
            </a>
        </td>
        <td><?php echo htmlspecialchars($u['serial_number']); ?></td>
        <td>
            <?php
                $status_class = 'badge-available';
                if ($u['status'] === 'Rented') $status_class = 'badge-rented';
                elseif ($u['status'] === 'Under Maintenance') $status_class = 'badge-maintenance';
            ?>
            <div style="margin-bottom: 6px;">
                <span class="badge <?php echo $status_class; ?>">
                    <?php echo htmlspecialchars($u['status']); ?>
                </span>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="unit_id" value="<?php echo $u['id']; ?>">
                <select name="status" onchange="this.form.submit()" style="padding: 4px 8px; margin: 0; font-size: 0.85rem;">
                    <option value="Available" <?php echo $u['status']=='Available'?'selected':''; ?>>Available</option>
                    <option value="Rented" <?php echo $u['status']=='Rented'?'selected':''; ?>>Rented</option>
                    <option value="Under Maintenance" <?php echo $u['status']=='Under Maintenance'?'selected':''; ?>>Under Maintenance</option>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
        </td>
        <td>
            <form method="POST" onsubmit="return confirm('Delete this unit?');">
                <input type="hidden" name="unit_id" value="<?php echo $u['id']; ?>">
                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require 'footer.php'; ?>
