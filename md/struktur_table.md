table : app
id_app int auto increment PK
nama_app varchar
partner_key text
partner_id varchar
status_app tinyint 0 = Developing , 1 = Live Production
created_date datetime
code text
shop_id text

table : token

id_token int auto increment PK
id_app int
access_token text
refresh_token text
created_date datetime (auto insert)

table category_api
parent_category_id varchar 10
category_id varchar 10 Primary Key
original_category_name varchar 50
display_category_name varchar 60
aktif tinyint 4
has_children tinyint 4
