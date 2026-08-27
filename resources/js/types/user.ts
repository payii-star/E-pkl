export interface User {
    id: BigInteger;
    uuid: string;
    name: string;
    email: string;
    password?: string;
    phone?: BigInteger;
    nim_nis?: string;
    asal_instansi?: string;
    asal_instansi_address?: string;
    asal_instansi_latitude?: number | null;
    asal_instansi_longitude?: number | null;
    asal_instansi_place_id?: string;
    role_id: BigInteger;
}
