// diri ang UI
import React from 'react';
import { Link, router } from '@inertiajs/react';
import { Payroll } from '@/types/models';

export default function index(){
    return (
        <div>
            <h1>Payroll</h1>
            <Link href={route('payrolls.create')}>Payroll Records</Link>
        </div>
    );
}
