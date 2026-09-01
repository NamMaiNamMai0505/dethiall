import './bootstrap';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

// Tom Select là hạ tầng dùng chung của các màn hình quản trị. Đóng gói cùng
// ứng dụng để không phụ thuộc CDN và phát tín hiệu khi module đã sẵn sàng.
window.TomSelect = TomSelect;
// Inline module scripts and Turbo-rendered fragments can be evaluated after
// this bundle. Keep a durable ready flag so they do not miss the one-shot
// event and leave native selects uninitialised.
window.__tomSelectReady = true;
window.dispatchEvent(new CustomEvent('app:tom-select-ready'));
