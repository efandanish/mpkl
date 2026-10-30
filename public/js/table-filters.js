document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | CAJ PEMAJUAN
    |--------------------------------------------------------------------------
    */

    const chargeSearch =
        document.getElementById('chargeSearch');

    const chargeStatusFilter =
        document.getElementById('chargeStatusFilter');


    function filterDevelopmentCharges() {

        const searchValue =
            chargeSearch
                ? chargeSearch.value.toLowerCase().trim()
                : '';

        const statusValue =
            chargeStatusFilter
                ? chargeStatusFilter.value
                : '';


        document
            .querySelectorAll('.charge-row')
            .forEach(function (row) {

                const searchData =
                    (row.dataset.search || '')
                        .toLowerCase();

                const paymentStatus =
                    row.dataset.paymentStatus || '';


                const matchesSearch =
                    searchValue === '' ||
                    searchData.includes(searchValue);

                const matchesStatus =
                    statusValue === '' ||
                    paymentStatus === statusValue;


                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }

            });
    }


    if (chargeSearch) {
        chargeSearch.addEventListener(
            'input',
            filterDevelopmentCharges
        );
    }


    if (chargeStatusFilter) {
        chargeStatusFilter.addEventListener(
            'change',
            filterDevelopmentCharges
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROJEK PEMBANGUNAN
    |--------------------------------------------------------------------------
    */

    const projectSearch =
        document.getElementById('projectSearch');

    const departmentFilter =
        document.getElementById('departmentFilter');

    const projectStatusFilter =
        document.getElementById('projectStatusFilter');

    const projectPaymentFilter =
        document.getElementById('projectPaymentFilter');


    function filterProjects() {

        const searchValue =
            projectSearch
                ? projectSearch.value.toLowerCase().trim()
                : '';

        const departmentValue =
            departmentFilter
                ? departmentFilter.value
                : '';

        const statusValue =
            projectStatusFilter
                ? projectStatusFilter.value
                : '';

        const paymentValue =
            projectPaymentFilter
                ? projectPaymentFilter.value
                : '';


        document
            .querySelectorAll('.project-row')
            .forEach(function (row) {

                const searchData =
                    (row.dataset.search || '')
                        .toLowerCase();

                const department =
                    row.dataset.department || '';

                const projectStatus =
                    row.dataset.projectStatus || '';

                const paymentStatus =
                    row.dataset.paymentStatus || '';


                const matchesSearch =
                    searchValue === '' ||
                    searchData.includes(searchValue);

                const matchesDepartment =
                    departmentValue === '' ||
                    department === departmentValue;

                const matchesProjectStatus =
                    statusValue === '' ||
                    projectStatus === statusValue;

                const matchesPaymentStatus =
                    paymentValue === '' ||
                    paymentStatus === paymentValue;


                if (
                    matchesSearch &&
                    matchesDepartment &&
                    matchesProjectStatus &&
                    matchesPaymentStatus
                ) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }

            });
    }


    if (projectSearch) {
        projectSearch.addEventListener(
            'input',
            filterProjects
        );
    }


    if (departmentFilter) {
        departmentFilter.addEventListener(
            'change',
            filterProjects
        );
    }


    if (projectStatusFilter) {
        projectStatusFilter.addEventListener(
            'change',
            filterProjects
        );
    }


    if (projectPaymentFilter) {
        projectPaymentFilter.addEventListener(
            'change',
            filterProjects
        );
    }

});
