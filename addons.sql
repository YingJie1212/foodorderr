-- Add-ons table
CREATE TABLE addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(6,2) NOT NULL DEFAULT 0
);

-- Relation table: which add-ons are available for which menu item
CREATE TABLE menu_item_addons (
    menu_item_id INT NOT NULL,
    addon_id INT NOT NULL,
    PRIMARY KEY (menu_item_id, addon_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    FOREIGN KEY (addon_id) REFERENCES addons(id)
);

-- Example data: some add-ons (some free, some paid)
INSERT INTO addons (name, price) VALUES
('Cheese', 3.00),
('Egg', 2.00),
('Bacon', 4.00),
('Extra Sauce', 0.00),
('Ice', 0.00);

-- Example: link add-ons to menu items
-- (Assume menu_items id 1 is Burger, id 2 is Drink)
INSERT INTO menu_item_addons (menu_item_id, addon_id) VALUES
(1, 1), -- Burger + Cheese
(1, 2), -- Burger + Egg
(1, 3), -- Burger + Bacon
(2, 4), -- Drink + Extra Sauce
(2, 5); -- Drink + Ice
