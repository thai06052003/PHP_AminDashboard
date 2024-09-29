<?php
if (!defined('_INCODE')) die('Access Deined...');
$data = [
    'pageTitle' => 'Cập nhật nhóm người dùng',
];

layout("header", 'admin', $data);
layout("sidebar", 'admin', $data);
layout('breadcrumb', 'admin', $data);

// lấy dữ liệu cũ của nhóm người dùng
$body = getBody('get'); //Yêu cầu lấy phương thức get

if (!empty($body['id'])){
    $userId = $body['id'];

    $userDetail = firstRaw("SELECT * FROM `users` WHERE id='$userId'");

    if (empty($userDetail)){
        //Không Tồn tại
        redirect('admin?module=users');
    }

}else{
    redirect('admin?module=users');
}

// truy vấn lấy danh sách nhóm
$allgroup = getRaw("SELECT id, name FROM groups ORDER BY name");

// xử lí cập nhật nhóm người dùng
if (isPost()) {
    $body = getBody();  // lấy tất cả dữ liệu trong form

    $errors = [];   // mảng lưu trữu lỗi

    // Validate họ tên: bắt buộc phải nhập, >=5 ký tự
    if(empty(trim($body['fullname'] ))) {
        $errors['fullname']['require'] = 'Họ tên bắt buộc phải nhập';
    }
    else if (strlen(trim($body['fullname'])) < 5) {
        $errors['fullname']['min'] = 'Họ tên phải lơn hơn 5 kí tự';
    }
    
    // Validate nhóm người dùng: bắt buộc phải chọn nhóm
    if (empty(trim($body['group_id'] ))) {
        $errors['group_id']['require'] = 'Vui lòng chọn nhóm người dùng';
    }

    // validate email: bắt buộc phải nhập, định dạng email, email phải duy nhất
    if (empty(trim($body['email'] ))) {
        $errors['email']['require'] = 'Email bắt buộc phải nhập';
    }
    else if (!isEmail(trim($body['email']))) {
        $errors['email']['isEmail'] = 'Email không hợp lệ';
    }
    else {
        $email = trim($body['email']);
        $sql = "SELECT id FROM users WHERE email='$email' AND id <> '$userId'";
        if (getRows($sql) > 0) {
            $errors['email']['Unique'] = 'Email đã tồn tại';

        }
    }

    // Validate nhập lại mật khẩu: bắt buộc phải nhập, giống trùng mật khẩu
    if (!empty(trim($body['password'] ))) {
        // Chỉ validate khi password đc nhập
        if (empty(trim($body['confirm_password'] ))) {
            $errors['confirm_password']['require'] = 'Xác nhận mật khẩu không được để trống';
        }
        else if (trim($body['password']) != trim($body['confirm_password'])) {
            $errors['confirm_password']['match'] = 'Hai mật khẩu không khớp nhau';
        }
    }

    

    // kiểm tra mảng errors
    if (empty($errors)) {
        // không có lỗi xảy ra
        $activeToken = sha1(uniqid().time());
        $dataUpdate = [
            'email'=> $body['email'],
            'fullname' => $body['fullname'],
            'group_id'=> $body['group_id'],
            'status' => $body['status'],
            'update_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty(trim($body['password'] ))) {
            $dataUpdate['password'] = password_hash($body['password'], PASSWORD_DEFAULT);
        }

        $condition = "id=$userId";
        $updateStatus = update('users', $dataUpdate, $condition);
        if ($updateStatus) { 
            setFlashData('msg', 'Cập nhật người dùng thành công');
            setFlashData('msg_type', 'success');

        }
        else {
            setFlashData('msg', 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau');
            setFlashData('msg_type', 'danger');
        }
    }
    else {
        setFlashData('msg', 'Vui lòng kiểm tra dữ liệu nhập vào');
        setFlashData('msg_type', 'danger');
        setFlashData('errors', $errors);
        setFlashData('old', $body);
    }
    redirect('admin?module=users&action=edit&id='.$userId);   // load lại trang thêm người dùng
}

$msg = getFlashData('msg');
$msgType = getFlashData('msg_type');
$errors = getFlashData('errors');
$old = getFlashData('old');

if (!empty($userDetail) && empty($old)) {
    $old = $userDetail;
}

/* 
Trường hợp 1: khi load trang (submit thành công), vẫn muốn xử dụng biến old => $old = $userDetail
Trường hợp 2: khi submit form => validate bị lỗi => Muốn giữ lại các dữ liệu vừa nhập thì phải setFashData cho dữ liệu vừa nhập và gán lại biến old
=> phải check để khi nhập dữ liệu bị SAI biến old ko bị sung đột dữ liệu giữa FlashData và $userDetail (dữ liệu vừa nhập và dữ liệu hiện tại của user đó)
*/

?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php
            getMsg($msg, $msgType);
        ?>
        <form action="" method="post">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="">Họ và tên</label>
                        <input type="text" class="form-control" name="fullname" placeholder="Họ và tên..." value="<?php echo old('fullname', $old) ?>">
                        <?php echo form_error('fullname', $errors, '<span class="error">', '</span>'); ?>
                    </div>
                    <div class="form-group">
                    <label for="">Nhóm người dùng</label>
                    <select name="group_id" id="" class="form-control">
                            <option value="0">Chọn nhóm</option>
                            <?php
                            if (!empty($allgroup)):
                                foreach($allgroup as $item):
                            ?>
                            <option value="<?php echo $item['id']?>" <?php echo (old('group_id', $old) == $item['id']) ? 'selected' : false ?>><?php echo $item['name']?></option>

                            <?php
                                endforeach;
                            endif;
                            ?>
                    </select>
                    <?php echo form_error('group_id', $errors, '<span class="error">', '</span>'); ?>
                </div>
                    <div class="form-group">
                        <label for="">Email</label>
                        <input type="text" class="form-control" name="email" placeholder="Email..." value="<?php echo old('email', $old) ?>">
                        <?php echo form_error('email', $errors, '<span class="error">', '</span>'); ?>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="">Mật khẩu</label>
                        <input type="password" class="form-control" name="password" placeholder="Mật khẩu (không nhập nếu không thay đổi) ...">
                        <?php echo form_error('password', $errors, '<span class="error">', '</span>'); ?>
                    </div>
                    <div class="form-group">
                        <label for="">Nhập lại mật khẩu</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Nhập lại mật khẩu (không nhập nếu không thay đổi) ...">
                        <?php echo form_error('confirm_password', $errors, '<span class="error">', '</span>'); ?>
                    </div>
                    <div class="form-group">
                        <label for="">Trạng thái</label>
                        <select name="status" id="" class="form-control">
                            <option value="0" <?php echo (old('status', $old) == 0) ? 'selected' : false  ?> >Chưa kích hoạt</option>
                            <option value="1" <?php echo (old('status', $old) == 1) ? 'selected' : false  ?>>Kích hoạt</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="<?php echo getLinkAdmin('users', 'lists') ?>" class="btn btn-success">Quay lại</a>
        </form>
    </div>
</section>

<?php
layout("footer", 'admin', $data);