-- 熟度选项表
CREATE TABLE cooking_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- 示例数据
INSERT INTO cooking_levels (name) VALUES
('Medium Rare'),
('Medium'),
('Medium Well'),
('Well Done');

-- 菜品支持的熟度选项关联表
CREATE TABLE menu_item_cooking_levels (
    menu_item_id INT NOT NULL,
    cooking_level_id INT NOT NULL,
    PRIMARY KEY (menu_item_id, cooking_level_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    FOREIGN KEY (cooking_level_id) REFERENCES cooking_levels(id)
);

-- 示例：假设id为1的Pepper Beef支持所有熟度
INSERT INTO menu_item_cooking_levels (menu_item_id, cooking_level_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4);
