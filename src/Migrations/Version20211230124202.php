<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use App\Component\Doctrine\Migrations\AbstractMigration;

class Version20211230124202 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE categories (
                id SERIAL NOT NULL,
                parent_id INT DEFAULT NULL,
                uuid UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                seo_title TEXT DEFAULT NULL,
                seo_meta_description TEXT DEFAULT NULL,
                seo_h1 TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                enabled BOOLEAN NOT NULL,
                level INT NOT NULL,
                lft INT NOT NULL,
                rgt INT NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE UNIQUE INDEX UNIQ_3AF34668D17F50A6 ON categories (uuid)');
        $this->sql('CREATE INDEX IDX_3AF34668727ACA70 ON categories (parent_id)');
        $this->sql('
            CREATE TABLE product_categories (
                product_id INT NOT NULL,
                category_id INT NOT NULL,
                PRIMARY KEY(product_id, category_id)
            )');
        $this->sql('CREATE INDEX IDX_A99419434584665A ON product_categories (product_id)');
        $this->sql('CREATE INDEX IDX_A994194312469DE2 ON product_categories (category_id)');
        $this->sql('CREATE INDEX IDX_A99419434584665A12469DE2 ON product_categories (product_id, category_id)');
        $this->sql('
            CREATE TABLE products (
                id SERIAL NOT NULL,
                main_variant_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                seo_title TEXT DEFAULT NULL,
                seo_meta_description TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                short_description TEXT DEFAULT NULL,
                description_tsvector TSVECTOR NOT NULL,
                fulltext_tsvector TSVECTOR NOT NULL,
                seo_h1 TEXT DEFAULT NULL,
                catnum VARCHAR(100) DEFAULT NULL,
                catnum_tsvector TSVECTOR NOT NULL,
                partno VARCHAR(100) DEFAULT NULL,
                partno_tsvector TSVECTOR NOT NULL,
                ean VARCHAR(100) DEFAULT NULL,
                selling_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                selling_to TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                selling_denied BOOLEAN NOT NULL,
                calculated_selling_denied BOOLEAN NOT NULL,
                hidden BOOLEAN NOT NULL,
                calculated_hidden BOOLEAN NOT NULL,
                using_stock BOOLEAN NOT NULL,
                stock_quantity INT DEFAULT NULL,
                out_of_stock_action VARCHAR(255) DEFAULT NULL,
                variant_type VARCHAR(32) NOT NULL,
                ordering_priority INT NOT NULL,
                uuid UUID NOT NULL,
                export_product BOOLEAN NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE UNIQUE INDEX UNIQ_B3BA5A5AD17F50A6 ON products (uuid)');
        $this->sql('CREATE INDEX IDX_B3BA5A5A391DDCC ON products (main_variant_id)');
        $this->sql('
            CREATE TABLE images (
                id SERIAL NOT NULL,
                entity_name VARCHAR(100) NOT NULL,
                entity_id INT NOT NULL,
                type VARCHAR(100) DEFAULT NULL,
                extension VARCHAR(5) NOT NULL,
                position INT DEFAULT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_E01FBE6A16EFC72D81257D5D8CDE5729 ON images (entity_name, entity_id, type)');
        $this->sql('
            ALTER TABLE
                categories
            ADD
                CONSTRAINT FK_3AF34668727ACA70 FOREIGN KEY (parent_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_categories
            ADD
                CONSTRAINT FK_A99419434584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_categories
            ADD
                CONSTRAINT FK_A994194312469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                products
            ADD
                CONSTRAINT FK_B3BA5A5A391DDCC FOREIGN KEY (main_variant_id) REFERENCES products (id) ON DELETE
            SET
                NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
    * @param \Doctrine\DBAL\Schema\Schema $schema
    */
    public function down(Schema $schema): void
    {
    }
}
