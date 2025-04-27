-- we don't know how to generate root <with-no-name> (class Root) :(

comment on database postgres is 'default administrative connection database';

create table public.migrations
(
    id        serial
        primary key,
    migration varchar(255) not null,
    batch     integer      not null
);

alter table public.migrations
    owner to postgres;

create table public.personal_access_tokens
(
    id             bigserial
        primary key,
    tokenable_type varchar(255) not null,
    tokenable_id   bigint       not null,
    name           varchar(255) not null,
    token          varchar(64)  not null
        constraint personal_access_tokens_token_unique
            unique,
    abilities      text,
    last_used_at   timestamp(0),
    expires_at     timestamp(0),
    created_at     timestamp(0),
    updated_at     timestamp(0)
);

alter table public.personal_access_tokens
    owner to postgres;

create index personal_access_tokens_tokenable_type_tokenable_id_index
    on public.personal_access_tokens (tokenable_type, tokenable_id);

create table public.users
(
    id                bigserial
        primary key,
    first_name        varchar(255)                                   not null,
    last_name         varchar(255)                                   not null,
    email             varchar(255)                                   not null
        constraint users_email_unique
            unique,
    contact_number    varchar(255)                                   not null
        constraint users_contact_number_unique
            unique,
    password          varchar(255)                                   not null,
    state             varchar(255)                                   not null,
    city              varchar(255)                                   not null,
    country           varchar(255)                                   not null,
    role              varchar(255) default 'user'::character varying not null
        constraint users_role_check
            check ((role)::text = ANY
                   ((ARRAY ['admin'::character varying, 'owner'::character varying, 'user'::character varying])::text[])),
    is_verified       boolean      default false                     not null,
    email_verified_at timestamp(0),
    remember_token    varchar(100),
    created_at        timestamp(0),
    updated_at        timestamp(0)
);

alter table public.users
    owner to postgres;

create table public.otp_verifications
(
    id         bigserial
        primary key,
    user_id    bigint       not null
        constraint otp_verifications_user_id_foreign
            references public.users
            on delete cascade,
    otp        varchar(255) not null,
    type       varchar(255) not null
        constraint otp_verifications_type_check
            check ((type)::text = ANY
                   ((ARRAY ['registration'::character varying, 'password_reset'::character varying])::text[])),
    expires_at timestamp(0) not null,
    created_at timestamp(0),
    updated_at timestamp(0)
);

alter table public.otp_verifications
    owner to postgres;

create table public.vehicles
(
    id           bigserial
        primary key,
    user_id      bigint       not null
        constraint vehicles_user_id_foreign
            references public.users
            on delete cascade,
    type         varchar(255) not null
        constraint vehicles_type_check
            check ((type)::text = ANY
                   ((ARRAY ['2-wheeler'::character varying, '4-wheeler'::character varying])::text[])),
    number_plate varchar(255) not null
        constraint vehicles_number_plate_unique
            unique,
    brand        varchar(255),
    model        varchar(255),
    color        varchar(255),
    created_at   timestamp(0),
    updated_at   timestamp(0)
);

alter table public.vehicles
    owner to postgres;

create table public.parking_locations
(
    id                       bigserial
        primary key,
    owner_id                 bigint               not null
        constraint parking_locations_owner_id_foreign
            references public.users
            on delete cascade,
    name                     varchar(255)         not null,
    address                  text                 not null,
    city                     varchar(255)         not null,
    state                    varchar(255)         not null,
    country                  varchar(255)         not null,
    zip_code                 varchar(255),
    latitude                 numeric(10, 7),
    longitude                numeric(10, 7),
    two_wheeler_capacity     integer              not null,
    four_wheeler_capacity    integer              not null,
    two_wheeler_hourly_rate  numeric(8, 2)        not null,
    four_wheeler_hourly_rate numeric(8, 2)        not null,
    is_active                boolean default true not null,
    created_at               timestamp(0),
    updated_at               timestamp(0)
);

alter table public.parking_locations
    owner to postgres;

create table public.parking_slot_availabilities
(
    id                  bigserial
        primary key,
    parking_location_id bigint       not null
        constraint parking_slot_availabilities_parking_location_id_foreign
            references public.parking_locations
            on delete cascade,
    vehicle_type        varchar(255) not null
        constraint parking_slot_availabilities_vehicle_type_check
            check ((vehicle_type)::text = ANY
                   ((ARRAY ['2-wheeler'::character varying, '4-wheeler'::character varying])::text[])),
    available_slots     integer      not null,
    total_slots         integer      not null,
    created_at          timestamp(0),
    updated_at          timestamp(0)
);

alter table public.parking_slot_availabilities
    owner to postgres;

create table public.parking_bookings
(
    id                  bigserial
        primary key,
    user_id             bigint                                             not null
        constraint parking_bookings_user_id_foreign
            references public.users
            on delete cascade,
    vehicle_id          bigint                                             not null
        constraint parking_bookings_vehicle_id_foreign
            references public.vehicles
            on delete cascade,
    parking_location_id bigint                                             not null
        constraint parking_bookings_parking_location_id_foreign
            references public.parking_locations
            on delete cascade,
    booking_type        varchar(255)                                       not null
        constraint parking_bookings_booking_type_check
            check ((booking_type)::text = ANY
                   ((ARRAY ['check_in'::character varying, 'advance'::character varying])::text[])),
    status              varchar(255) default 'upcoming'::character varying not null
        constraint parking_bookings_status_check
            check ((status)::text = ANY
                   ((ARRAY ['upcoming'::character varying, 'checked_in'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])),
    check_in_time       timestamp(0)                                       not null,
    check_out_time      timestamp(0)                                       not null,
    duration_hours      integer                                            not null,
    amount              numeric(8, 2)                                      not null,
    qr_code             varchar(255),
    created_at          timestamp(0),
    updated_at          timestamp(0)
);

alter table public.parking_bookings
    owner to postgres;

create table public.parking_time_slots
(
    id                  bigserial
        primary key,
    parking_location_id bigint       not null
        constraint parking_time_slots_parking_location_id_foreign
            references public.parking_locations
            on delete cascade,
    vehicle_type        varchar(255) not null
        constraint parking_time_slots_vehicle_type_check
            check ((vehicle_type)::text = ANY
                   ((ARRAY ['2-wheeler'::character varying, '4-wheeler'::character varying])::text[])),
    date                date         not null,
    start_time          time(0)      not null,
    end_time            time(0)      not null,
    available_slots     integer      not null,
    created_at          timestamp(0),
    updated_at          timestamp(0)
);

alter table public.parking_time_slots
    owner to postgres;

