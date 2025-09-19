CREATE TABLE tx_sxfavicon_config (
                                     uid int AUTO_INCREMENT PRIMARY KEY,
                                     tstamp int DEFAULT 0 NOT NULL,
                                     crdate int DEFAULT 0 NOT NULL,
                                     site_identifier varchar(255) NOT NULL DEFAULT '',
                                     svg int DEFAULT 0 NOT NULL,
                                     light int DEFAULT 0 NOT NULL,
                                     dark int DEFAULT 0 NOT NULL,

                                     KEY site (site_identifier)
);
