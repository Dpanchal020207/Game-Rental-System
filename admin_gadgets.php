<?php
require 'db.php';
if (!isAdmin()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $daily_price = (float)$_POST['daily_price'];
        $category_id = (int)$_POST['category_id'];
        
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
        }
        
        mysqli_query($conn, "INSERT INTO gadgets (title, description, daily_price, category_id, image) VALUES ('$title', '$description', $daily_price, $category_id, '$image')");
        $new_gadget_id = mysqli_insert_id($conn);
        
        if ($new_gadget_id && isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $k => $name) {
                if ($_FILES['gallery_images']['error'][$k] == 0) {
                    $ext_img = time() . '_' . $k . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
                    if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$k], "uploads/" . $ext_img)) {
                        mysqli_query($conn, "INSERT INTO gadget_images (gadget_id, image) VALUES ($new_gadget_id, '$ext_img')");
                    }
                }
            }
        }
    }
    elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['gadget_id'];
        mysqli_query($conn, "DELETE FROM gadgets WHERE id = $id");
    }
    redirect('admin_gadgets.php');
}

$res = mysqli_query($conn, "SELECT g.*, c.name as cat_name FROM gadgets g JOIN categories c ON g.category_id = c.id");
$gadgets = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $gadgets[] = $row;
    }
}

$res_cat = mysqli_query($conn, "SELECT * FROM categories");
$categories = [];
if ($res_cat) {
    while ($row = mysqli_fetch_assoc($res_cat)) {
        $categories[] = $row;
    }
}

require 'header.php';
?>

<h2>Manage Gadgets (Catalog)</h2>

<div style="background: var(--container-bg); padding:30px; border:1px solid var(--border-color); margin-bottom: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <h3>Add New Gadget</h3>
    <form method="POST" enctype="multipart/form-data" style="max-width: 500px;">
        <label>Title:</label>
        <input type="text" name="title" required>
        <label>Description:</label>
        <textarea name="description"></textarea>
        <label>Daily Rental Price:</label>
        <input type="number" step="0.01" name="daily_price" required>
        <label>Category:</label>
        <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label>Primary Image:</label>
        <input type="file" name="image">
        <label>Additional Gallery Images (Multiple selection supported):</label>
        <input type="file" name="gallery_images[]" multiple>
        <button type="submit" name="add" class="btn">Add Gadget</button>
    </form>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Title</th>
        <th>Category</th>
        <th>Price/Day</th>
        <th>Action</th>
    </tr>
    <?php foreach ($gadgets as $g): ?>
    <tr>
        <td><?php echo $g['id']; ?></td>
        <td>
            <?php if ($g['image']): ?>
                <a href="gadget_details.php?id=<?php echo $g['id']; ?>" target="_blank" title="View product page">
                    <img src="uploads/<?php echo $g['image']; ?>" width="50" height="50" style="border-radius: 6px; object-fit: cover;">
                </a>
            <?php endif; ?>
        </td>
        <td>
            <a href="gadget_details.php?id=<?php echo $g['id']; ?>" target="_blank" style="color: inherit; text-decoration: none; font-weight: 600;" onmouseover="this.style.color='#00d2ff'" onmouseout="this.style.color='inherit'" title="View product page">
                <?php echo htmlspecialchars($g['title']); ?> &#8599;
            </a>
        </td>
        <td><?php echo htmlspecialchars($g['cat_name']); ?></td>
        <td>₹<?php echo htmlspecialchars($g['daily_price']); ?></td>
        <td>
            <a href="admin_edit_gadget.php?id=<?php echo $g['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; margin-bottom: 5px;">Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this gadget and all its units?');">
                <input type="hidden" name="gadget_id" value="<?php echo $g['id']; ?>">
                <button type="submit" name="delete" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require 'footer.php'; ?>
