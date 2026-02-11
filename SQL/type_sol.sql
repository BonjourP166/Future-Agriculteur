CREATE TABLE IF NOT EXISTS type_sol(
soil_id INT(11),
soil_name VARCHAR(100),
texture_class VARCHAR(100),
sand_pct INT(11),
silt_pct INT(11),
clay_pct INT(11),
pH DECIMAL(10,2),
organic_matter_pct DECIMAL(10,2),
water_holding_capacity INT(11),
drainage VARCHAR(100),
irrigation_frequency VARCHAR(100)
);


INSERT INTO type_sol(soil_id,soil_name,texture_class,sand_pct,silt_pct,clay_pct,pH,organic_matter_pct,water_holding_capacity,drainage,irrigation_frequency)
VALUES
(1,'Sol sableux','Sable',80,10,10,6.1,2.4,74,'Modere','Frequente'),
(2,'Sol limoneux','Limon',40,40,20,6.8999999999999995,4.0,63,'Rapide','Frequente'),
(3,'Sol argileux','Argile',20,30,50,8.1,3.3,56,'Lent','Moderee'),
(4,'Sol calcaire','Calcaire',30,30,40,8.1,1.8000000000000003,64,'Modere','Moderee'),
(5,'Sol humifère','Humus',35,35,30,6.7,2.9000000000000004,25,'Modere','Moderee'),
(6,'Sol volcanique','Volcanique',45,25,30,7.0,1.5999999999999999,55,'Modere','Frequente'),
(7,'Sol alluvial','Alluvial',50,30,20,7.0,4.3,73,'Modere','Faible'),
(8,'Sol tourbeux','Tourbe',25,35,40,8.0,1.9,44,'Lent','Moderee'),
(9,'Sol siliceux','Silice',60,25,15,5.8999999999999995,2.6999999999999997,45,'Rapide','Frequente'),
(10,'Sol marneux','Marne',30,40,30,5.8,3.4,38,'Rapide','Frequente'),
(11,'Sol caillouteux','Cailloux',55,25,20,7.1,3.0999999999999996,27,'Lent','Moderee'),
(12,'Sol forestier','Forestier',40,35,25,6.5,2.1999999999999997,63,'Modere','Frequente'),
(13,'Sol mediterraneen','Mediterraneen',50,30,20,7.2,2.0999999999999996,35,'Rapide','Moderee'),
(14,'Sol montagnard','Montagnard',45,35,20,8.1,1.2,40,'Rapide','Moderee'),
(15,'Sol loameux','Loam',40,40,20,5.7,3.3000000000000003,59,'Rapide','Faible'),
(16,'Sol sablo-limoneux','Sablo-limon',60,25,15,6.6,4.2,49,'Rapide','Moderee'),
(17,'Sol argilo-limoneux','Argilo-limon',30,45,25,5.8,4.2,60,'Rapide','Faible'),
(18,'Sol argilo-sableux','Argilo-sable',35,25,40,6.1,4.9,72,'Modere','Moderee'),
(19,'Sol riche organique','Organique',30,30,40,5.5,3.2,46,'Lent','Frequente'),
(20,'Sol drainant','Drainant',65,20,15,6.3,3.3,40,'Rapide','Frequente');
