import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

export const showSuccessAlert = (title: string, text?: string) => {
    void Swal.fire({
        title,
        text,
        icon: 'success',
        confirmButtonColor: '#8646e8',
        timer: 1800,
        timerProgressBar: true,
    });
};
