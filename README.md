# Hệ thống điểm danh

Ứng dụng Laravel quản lý hồ sơ giảng viên và các nghiệp vụ điểm danh. Môi trường đang cấu hình dùng PHP 8.2, Laravel 12 và MySQL. Tệp `.env` của mỗi máy chứa thông tin kết nối riêng và không đưa lên kho mã nguồn.

## Đã triển khai

- Đăng nhập và đăng xuất.
- Quản lý khoa/đơn vị và danh mục học hàm, học vị.
- Danh mục học phần: mã môn, tên, số tín chỉ, khoa phụ trách; tìm kiếm, lọc, chỉnh sửa và bật/tắt trạng thái.
- Lớp học phần: mở lớp theo học kỳ/năm học, phân công giảng viên/đơn vị, khai báo hình thức đào tạo, thứ/giờ/phòng, khoảng thời gian và số buổi dự kiến; phát hiện lịch trùng giảng viên/phòng. Giảng viên thấy danh sách chỉ đọc của lớp được phân công; lãnh đạo khoa/cán bộ quản lý bị giới hạn theo đơn vị.
- Người học: CRUD hồ sơ, cảnh báo thông tin thiếu/bất thường và nhập danh sách bằng CSV UTF-8 tương thích Excel.
- Ghi danh: Phòng đào tạo có thể tìm, thêm và kết thúc hiệu lực ghi danh; lưu ngày bắt đầu/kết thúc cùng nhật ký ai thực hiện, ai duyệt, trước/sau và lý do. Buổi điểm danh lấy roster đúng ngày; hồ sơ/lịch sử không bị xóa khi rút học phần.
- Điểm danh theo buổi: tạo buổi từ ngày học thực tế theo lịch lớp; phiên chụp danh sách ghi danh có hiệu lực tại ngày đó.
- Ghi nhận các trạng thái có mặt, đi muộn, vắng có phép/không phép, khác hoặc chưa xác định; hỗ trợ đánh dấu nhanh có mặt, ghi người thực hiện/thời gian, lưu lịch sử đổi trạng thái và xác nhận buổi. Giảng viên được sửa trực tiếp trong hạn cấu hình; sau hạn phải gửi đề nghị để cán bộ phê duyệt.
- Điểm kiểm tra: cấu hình nhiều cột thành phần với thang điểm, loại điểm, trọng số và hạn nhập/sửa; vắng kiểm tra và chưa có điểm là trạng thái riêng, không quy về 0. Sửa sau hạn cần lý do và duyệt, có lịch sử trước/sau.
- Portal người học: tài khoản liên kết một hồ sơ, chỉ xem chuyên cần/điểm của mình và gửi yêu cầu kiểm tra sai sót; yêu cầu không cập nhật dữ liệu trước khi được duyệt.
- Cảnh báo: ngưỡng vắng không phép và thời gian nhắc xác nhận buổi cấu hình được; hỗ trợ người học chưa xếp lớp, lớp thiếu dữ liệu, buổi quá hạn và vắng vượt ngưỡng. Cán bộ phân công/xử lý và lưu nhật ký.
- Báo cáo: lọc chuyên cần theo ngày, đơn vị, năm học, học kỳ, lớp, người học, trạng thái; báo cáo điểm theo lớp/thành phần/trạng thái; xuất CSV UTF-8 mở bằng Excel.
- Hồ sơ giảng viên: tạo/cập nhật hồ sơ và tài khoản đăng nhập cùng luồng; admin đặt hoặc đổi mật khẩu ngay trong form hồ sơ, email hồ sơ được đồng bộ sang tài khoản. Giảng viên tự kê khai được liên kết hồ sơ với tài khoản đang đăng nhập.
- Lưu CCCD, thông tin ngân hàng và giấy tờ trong vùng lưu trữ riêng; giới hạn quyền xem dữ liệu nhạy cảm.
- Quy trình gửi hồ sơ, yêu cầu chỉnh sửa, xác minh và duyệt; ghi lịch sử thay đổi.
- Quản lý tài khoản và vai trò qua giao diện admin; tài khoản giảng viên/người học liên kết một-một với hồ sơ tương ứng, lãnh đạo/cán bộ được gán đơn vị. Danh sách nhóm theo vai trò, có tìm kiếm, modal tạo/sửa và xóa có bảo vệ.
- Phân quyền: admin có toàn quyền quản trị hệ thống và nghiệp vụ, bao gồm hồ sơ nhạy cảm; Phòng đào tạo quản lý đào tạo toàn trường; lãnh đạo khoa/cán bộ quản lý xem và xử lý trong đơn vị; giảng viên thao tác lớp được phân công; sinh viên chỉ xem hồ sơ của mình.

## Chạy trên Windows/XAMPP

Mở PowerShell tại thư mục dự án. Nếu PHP chưa có trong `PATH`, dùng đường dẫn XAMPP đầy đủ như ví dụ dưới đây.

```powershell
$php = 'D:\xampp82\php\php.exe'
& $php artisan --version
```

Tạo database MySQL (mặc định là `diemdanh`) trong MySQL của XAMPP, rồi điền thông tin kết nối vào `.env` (`DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Không chia sẻ hoặc commit `.env`.

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=base64:.+' -Quiet)) { & $php artisan key:generate }
& $php artisan migrate
npm install
npm run build
& $php artisan serve
```

Ứng dụng mặc định chạy tại `http://127.0.0.1:8000`. Nếu dự án đã có `.env` và APP_KEY, không cần tạo lại khóa. Có thể chạy Vite bằng `npm run dev` trong một cửa sổ terminal riêng khi đang phát triển giao diện.

## Tạo tài khoản quản trị

Lệnh sau tạo tài khoản nếu email chưa tồn tại, hoặc nâng quyền tài khoản hiện có. Lệnh sẽ hỏi mật khẩu và xác nhận trong terminal; mật khẩu tối thiểu 12 ký tự.

```powershell
& $php artisan app:bootstrap-admin admin@example.edu.vn --name="Quản trị hệ thống"
```

Thêm `--reset-password` nếu cần đặt lại mật khẩu cho tài khoản hiện có.

## Nạp học phần mẫu

Seeder tạo 10 học phần mẫu và có thể chạy lại an toàn theo mã học phần:

```powershell
& $php artisan db:seed --class=Database\Seeders\CourseSeeder
```

## Dữ liệu mẫu đào tạo

Seeder chính tạo 10 học phần, 10 giảng viên, 10 người học, 12 lớp học phần có thời khóa biểu, 7 người học có ghi danh, hai buổi điểm danh mẫu (một đang mở, một đã xác nhận), một cột điểm có dữ liệu đã chấm/vắng, ngưỡng cảnh báo và tài khoản Phòng đào tạo cùng sinh viên dùng riêng cho môi trường phát triển. Chạy lệnh sau sau khi migrate; các seeder có thể chạy lại an toàn và không ghi đè trạng thái điểm danh/điểm đã được người dùng sửa.

```powershell
& $php artisan db:seed
```

Tài khoản mẫu: Phòng đào tạo `training.office@example.test` và sinh viên `student.demo@example.test`, cả hai dùng mật khẩu `password`. Chỉ dùng trong môi trường phát triển; không dùng tài khoản/mật khẩu mẫu trong production. Tài khoản sinh viên mẫu liên kết `SV-MAU-001`.

Mẫu import được tải trực tiếp tại mục Người học dưới dạng CSV UTF-8 có BOM để mở bằng Excel. Giữ nguyên tên cột; ngày sinh dùng `YYYY-MM-DD`, trạng thái dùng `active`, `paused`, `inactive`, `graduated` hoặc `dropped`; `department_code` phải là mã khoa/đơn vị đã khai báo. File tối đa 10 MB và 2.000 hồ sơ. Nếu một dòng sai hoặc mã bị trùng, toàn bộ file bị từ chối và không ghi hồ sơ nào.

## Trạng thái và hướng phát triển

Các phần còn cần triển khai hoặc phụ thuộc tích hợp bên ngoài:

1. Kết nối SSO/tài khoản chính thức UTM và đồng bộ dữ liệu với Unisoft; hiện dùng đăng nhập cục bộ và CSV vì chưa có API/credential tích hợp.
2. Phê duyệt thay đổi roster trước khi áp dụng, thông báo trong ứng dụng cho giảng viên và chức năng báo người học thực tế chưa có tên.
3. Công thức/tổng điểm kiểm tra cuối cùng và báo cáo PDF/Excel chuẩn của Nhà trường; hiện có trọng số thành phần, bảng tổng hợp và xuất CSV.
4. Các báo cáo tổng hợp toàn học kỳ/người học trên mọi học phần, báo cáo xử lý cảnh báo, sao lưu/khôi phục và cấu hình thời hạn sửa theo từng đơn vị.
5. Tự chạy scheduler cần cấu hình Windows Task Scheduler gọi `php artisan schedule:run` mỗi phút trên máy triển khai.

## Kiểm tra

```powershell
& $php artisan test
```

Chạy `php artisan migrate` để đồng bộ các migration mới với database trong `.env`.
