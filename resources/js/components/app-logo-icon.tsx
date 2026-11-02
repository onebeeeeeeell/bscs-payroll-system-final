import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({ alt = 'Payroll System logo', ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    return <img {...props} src="/imgs/payroll.png" alt={alt} />;
}
