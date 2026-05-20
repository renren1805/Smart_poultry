-- Fix the update_inventory_on_delivery trigger
-- The original trigger had a syntax error trying to reference oi.quantity in the SET clause

-- Drop the old trigger
DROP TRIGGER IF EXISTS `update_inventory_on_delivery`;

-- Create the corrected trigger
DELIMITER //

CREATE TRIGGER `update_inventory_on_delivery` 
AFTER UPDATE ON `orders`
FOR EACH ROW
BEGIN
    IF OLD.status != 'Delivered' AND NEW.status = 'Delivered' THEN
        -- Update inventory based on order items using JOIN
        UPDATE inventory_items i
        INNER JOIN order_items oi ON i.id = oi.product_id
        SET i.current_quantity = i.current_quantity - oi.quantity,
            i.updated_at = NOW()
        WHERE oi.order_id = NEW.id;
        
        -- Log stock movements
        INSERT INTO stock_movements (product_id, product_name, movement_type, quantity, reference, user_name, created_at)
        SELECT oi.product_id, i.name, 'out', oi.quantity, CONCAT('Order Delivered #', NEW.id), c.fullname, NOW()
        FROM order_items oi
        JOIN inventory_items i ON oi.product_id = i.id
        JOIN customers c ON NEW.customer_id = c.id
        WHERE oi.order_id = NEW.id;
    END IF;
END//

DELIMITER ;

SELECT 'Trigger fixed successfully!' as message;
