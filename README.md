# Viet Nam saleor for WooCommerce

- Contributors: nvtienanh
- Donate link: https://paypal.me/nvtienanh
- Tags: woocommerce, locations, provinces, regions, vietnam, districs, governorates, counties, cantons
- Stable tag: 1.2.3
- Requires at least: 4.0
- Tested up to: 5.5
- Requires PHP: 7.0
- WC requires at least: 3.0.x
- WC tested up to: 4.5.2
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin thay đổi thông tin địa chỉ WooCommerce cho phù hợp với đơn vị hành chính ở Việt Nam

## Giới thiệu

Plugin này thay đổi thông tin địa chỉ cho phù hợp với đơn vị hành chính tại Việt Nam là **Tỉnh/Thành phố**, **Quận/Huyện - Xã/Phường** trong cả phần giao diện người dùng và phẩn quản lý của admin.

- Thay đổi phần hiển thị thông tin địa chỉ (frontend và backend) cho phù hợp với người Việt Nam.

- Để tránh chỉnh sửa quá nhiều database và loại bỏ các field không cần thiết khi thực hiện các bước đặt hàng, thanh toán ở Việt Nam:
    - Ẩn field `Country` vì mặc định chỉ dùng cho Việt Nam
    - Sửa `state` -> ` Tỉnh / Thành Phố` 
    - Sửa `city` -> `Quận / Huyện -  Xã / Phường`
    - Ẩn field `address_2`
    - Ẩn field `last_name`
    - Sửa field `first_name` -> `Họ và tên`
    - Chỉnh field `phone` thành bắt buộc
    - Chỉnh field `email` thành tùy chọn
- Tích hợp tính phí ship khi đặt hàng:
    - [x] Giao hàng tiết kiệm
    - [ ] Giao hàng nhanh
    - [x] Viettel Post
    - [ ] VNPost
    - [ ] Ninja vận
- Hiển thị trong phần tạo, chỉnh sửa đơn hàng

= Supported Countries =

* VN Việt Nam

## Ngôn ngữ hỗ trợ

* Tiếng Việt (Vietnamese)
* Tiếng Anh (English)

## Cài đặt

Xem chi tiết tại: https://nvtienanh.info/development/viet-nam-saleor-for-woocommerce/


## Screenshots

Dưới đây làm một số màn hình demo ở `localhost`, theme sử dụng là `storefront`:

1. Màn hình phần thanh toán

![Màn hình thanh toán](screenshot-1.png)

2.  Màn hình phần thông tin đơn hàng

![Màn hình giỏ hàng](screenshot-2.png)

3. Màn hình thiết lập Viettel Post

![Màn hình thanh toán](screenshot-3.png)

4. Màn hình cấu hình phương thức Giao hàng tiết kiệm

![Màn hình giao hàng tiết kiệm](screenshot-4.png)

5. Màn hình giao diện Admin tạo đơn hàng

![Màn hình Admin tạo đơn hàng](screenshot-5.png)

6. Quản lý vận đơn

![Màn hình Admin quản lý vận đơn](screenshot-6.png)

## Hỗ trợ

= How do I report bugs? =

Mọi thắc mắc, góp ý có thể thực hiện bằng cách: [Create new issue](https://github.com/nvtienanh/viet-nam-saleor-for-woocommerce/issues/new/choose).

Mọi PRs trên tinh thần xây dựng đều được hoan nghênh.

= Hỗ trợ đặc biệt? =

Plugin này hoàn toàn miễn phí, tuy nhiên nếu bạn cần hỗ trợ đặc biệt.
Vui lòng liên hệ với tôi [@nvtienanh](mailto:contact@nvtienanh.info):


## Lịch sử phát triển

## 1.2.3 - September 21, 2020
* Sửa lỗi: Hiển thị tên trường thông tin không chính xác

## 1.2.2 - September 07, 2020
* Fix lỗi: Packets larger than `max_allowed_packet` are not allowed gặp phải ở một số hosting cấu hình yếu

### 1.2.1 - September 06, 2020
* Cải thiện tốc độ import database

### 1.2.0 - September 04, 2020
* Thêm tính năng giao hàng Viettel Post

### 1.1.1 - August 26, 2020
* Thêm tính năng điền thông tin giao hàng vào phần quản lý đơn hàng: số điện thoại, loại dịch vụ giao hàng, người trả ship
* Thêm tính năng của GHTK: tạo vận đơn, hủy vận đơn và lưu lịch sử
* Lưu thông tin hành chính của VN vào database thay vì dùng file php
* Thêm ngôn ngữ Anh và Việt

### 1.0.3 - August 19, 2020
* Sửa lỗi không select được khi dùng WC 4.4.x

### 1.0.2 - August 18, 2020
* Cải thiện tính năng

### 1.0.1 - August 18, 2020
* Sửa lỗi để đăng lên wordress.org

### 1.0.0 - August 16, 2020
* Phiên bản đầu tiên

## Tác giả
### Anh Nguyễn
- Webiste: https://nvtienanh.info
- Email: nvtienanh@gmail.com
- Linkedin: https://www.linkedin.com/in/nvtienanh/

### Coffe 4 me
Bạn có thể mua cà phê để tôi code thêm tính năng mới :)

![Donate MoMo](assets/images/momo.jpg)
