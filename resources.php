<?php
session_start();
require_once 'db.php';

// جلب الموارد
$stmt = $pdo->query("SELECT resource.*, users.full_name FROM resource JOIN users ON resource.owner_id = users.user_id ORDER BY resource.created_at DESC");
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة الموارد</title>
    <style>
        /* تنسيق عام */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f8f9fa; display: flex; height: 100vh; }
        
        /* السايد بار */
        .sidebar { width: 260px; background: #1e293b; color: #fff; padding: 25px; }
        .sidebar h2 { color: #38bdf8; margin-bottom: 30px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: #94a3b8; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 6px; }
        .sidebar ul li a.active { background-color: #334155; color: #fff; }
        
        /* المحتوى */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .resources-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .resource-card { background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .resource-card img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .badge { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 4px; font-size: 0.75em; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>المنصة الذكية</h2>
        <ul>
            <li><a href="dashboard.php">لوحة التحكم</a></li>
            <li><a href="add_resource.php">إضافة مورد جديد</a></li>
            <li><a href="resources.php" class="active">الموارد المتاحة</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h2 style="margin-bottom: 20px;">قائمة الموارد والمصادر المتاحة</h2>
        <div class="resources-grid">
    <?php foreach ($resources as $res): ?>
        <div class="resource-card">
            
            <!-- صورة المورد والتصنيفات العلوية -->
            <div class="card-img-container">
                <?php if (!empty($res['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($res['image_path']); ?>" alt="صورة المورد">
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #eee; color: #94a3b8;">لا توجد صورة</div>
                <?php endif; ?>
                
                <span class="badge-category"><?php echo htmlspecialchars($res['category']); ?></span>
                <span class="badge-type"><?php echo ($res['availability_type'] == 'donation') ? 'تبرع' : 'تبادل'; ?></span>
            </div>

            <!-- تفاصيل المورد -->
            <div class="card-body">
                <h3><?php echo htmlspecialchars($res['name']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($res['description'])); ?></p>
                
                <div class="card-details">
                    <div><span>الحالة:</span> <strong><?php echo htmlspecialchars($res['condition']); ?></strong></div>
                    <div><span>الموقع:</span> <strong><?php echo htmlspecialchars($res['location']); ?></strong></div>
                </div>
            </div>

            <!-- أسفل الكارت (المالك وزر الطلب) -->
            <div class="card-footer">
                <span>بواسطة: <?php echo htmlspecialchars($res['full_name']); ?></span>
                
                <!-- هذا هو زر الطلب الذي كنا نبحث عنه -->
                <a href="request_resource.php?id=<?php echo $res['resource_id']; ?>" 
                   class="btn-action" 
                   onclick="return confirm('هل أنت متأكد أنك تريد طلب هذا المورد؟');">
                   طلب المورد
                </a>
            </div>

        </div>
    <?php endforeach; ?>
</div>

</body>
</html>