-- 菜品分类表
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- 示例数据
INSERT INTO categories (name) VALUES
('主食'),
('饮品'),
('小吃'),
('甜品'),
('特色菜');

-- 菜品表建议增加 category_id 字段
-- ALTER TABLE menu_items ADD COLUMN category_id INT;
-- 并建立外键关系
-- ALTER TABLE menu_items ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id);
