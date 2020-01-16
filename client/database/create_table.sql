create table tbl_projects(
    project_id      INTEGER,
    name            VARCHAR(200),
    document_root   VARCHAR(200)
);
create UNIQUE index idx_project_id ON tbl_projects(project_id);

alter table tbl_projects add (document_root   VARCHAR(200));

create table tbl_file_controls(
    project_id  INTEGER,
    rel_path    varchar(500),
    abs_path    varchar(500),
    md5         varchar(100),
    content     BLOB     
);
create index idx_file_controls_project_id ON tbl_projects(project_id);

create table tbl_deleted_file(
    project_id  INTEGER,
    act_type_id integer,
    rel_path    varchar(500),
    md5         varchar(100),
    content     BLOB     
);
create index idx_deleted_file_id ON tbl_deleted_file(project_id);
create index idx_rel_path_id ON tbl_deleted_file(rel_path);
