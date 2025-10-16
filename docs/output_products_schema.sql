-- ==================== OUTPUT PRODUCTS UNIFIED SYSTEM ====================
-- This is the central product catalog that aggregates all sellable items/services
-- from different modules (events, sales, rental, membership) into one unified system.

CREATE TABLE output_products (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    module_id CHAR(36) NOT NULL,
    
    -- Product identification
    product_type VARCHAR(50) NOT NULL, -- 'event', 'rental', 'sales_item', 'membership'
    product_id CHAR(36) NOT NULL, -- References the actual product in module table
    
    -- Display and visibility
    is_visible BOOLEAN DEFAULT TRUE, -- Can be hidden without deletion
    is_public BOOLEAN DEFAULT TRUE, -- Public on company website
    is_marketplace_featured BOOLEAN DEFAULT FALSE, -- Featured on main marketplace (www-domain)
    
    -- Marketplace tracking
    referred_by VARCHAR(100), -- 'marketplace', 'direct', 'social', etc.
    marketplace_priority INTEGER DEFAULT 0, -- For sorting on marketplace
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36) NOT NULL,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES tenant_admins(id),
    
    -- Ensure one output_product per actual product
    UNIQUE(product_type, product_id)
);

-- ==================== EVENTS SYSTEM REDESIGN ====================
-- Replaces tenant_events with better structure aligned to output_products

CREATE TABLE events_eventdata (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_id CHAR(36) NOT NULL,
    output_product_id CHAR(36), -- Links to unified product catalog
    
    -- Event details
    title VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Timing
    start_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_date DATE,
    end_time TIME,
    
    -- Venue
    venue_name VARCHAR(255),
    event_address VARCHAR(255),
    event_city VARCHAR(255),
    event_postal VARCHAR(50),
    
    -- Capacity and restrictions
    max_attendees INT,
    min_attendees INT,
    age_limit INT,
    
    -- Membership and exclusivity
    is_member_exclusive BOOLEAN DEFAULT FALSE,
    
    -- Pricing
    ticket_price DECIMAL(10,2) DEFAULT 0,
    member_ticket_price DECIMAL(10,2), -- Special member pricing
    
    -- Media
    event_image_url VARCHAR(500),
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Audit
    created_by CHAR(36) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (output_product_id) REFERENCES output_products(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES tenant_admins(id)
);

-- ==================== EVENTS CHECK-IN SYSTEM ====================
-- Tracks attendees, payment status, and check-in

CREATE TABLE events_checkin (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    event_id CHAR(36) NOT NULL,
    customer_id CHAR(36), -- References tenant_customers if exists
    
    -- Attendee information (if no customer_id)
    attendee_name VARCHAR(255),
    attendee_email VARCHAR(255),
    attendee_phone VARCHAR(50),
    
    -- Ticket details
    ticket_type VARCHAR(50) DEFAULT 'regular', -- 'regular', 'member', 'vip', etc.
    ticket_price DECIMAL(10,2) NOT NULL,
    
    -- Payment tracking
    payment_status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'paid', 'refunded', 'cancelled'
    payment_method VARCHAR(50), -- 'cash', 'card', 'vipps', 'online', etc.
    paid_at DATETIME,
    
    -- Check-in tracking
    checked_in BOOLEAN DEFAULT FALSE,
    check_in_time DATETIME,
    checked_in_by CHAR(36), -- Admin who checked them in
    
    -- Notes
    notes TEXT,
    
    -- Audit
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36) NOT NULL,
    
    FOREIGN KEY (event_id) REFERENCES events_eventdata(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES tenant_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (checked_in_by) REFERENCES tenant_admins(id),
    FOREIGN KEY (created_by) REFERENCES tenant_admins(id)
);

-- ==================== INDEXES ====================
CREATE INDEX idx_output_products_company ON output_products(company_id);
CREATE INDEX idx_output_products_module ON output_products(module_id);
CREATE INDEX idx_output_products_type ON output_products(product_type);
CREATE INDEX idx_output_products_visibility ON output_products(is_visible, is_public, is_marketplace_featured);
CREATE INDEX idx_output_products_marketplace ON output_products(is_marketplace_featured, marketplace_priority);

CREATE INDEX idx_events_eventdata_company ON events_eventdata(company_id);
CREATE INDEX idx_events_eventdata_output ON events_eventdata(output_product_id);
CREATE INDEX idx_events_eventdata_date ON events_eventdata(start_date, end_date);
CREATE INDEX idx_events_eventdata_active ON events_eventdata(is_active);

CREATE INDEX idx_events_checkin_event ON events_checkin(event_id);
CREATE INDEX idx_events_checkin_customer ON events_checkin(customer_id);
CREATE INDEX idx_events_checkin_payment ON events_checkin(payment_status);
CREATE INDEX idx_events_checkin_status ON events_checkin(checked_in, check_in_time);

-- ==================== MIGRATION NOTES ====================
-- After creating these tables, you need to:
-- 1. Migrate data from tenant_events to events_eventdata
-- 2. Create output_products entries for each event
-- 3. Update all API endpoints to use new structure
-- 4. Add output_product_id columns to: sales_items, rental_objects, membership_plans
