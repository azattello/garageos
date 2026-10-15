type BrandLogoProps = {
    className?: string;
    compact?: boolean;
    showName?: boolean;
};

export default function BrandLogo({ className = '', compact = false, showName = true }: BrandLogoProps) {
    const icon = (
        <img
            alt="GarageOS"
            className={`${className || 'h-10 w-10 shrink-0'} object-contain`}
            src="/logo.jpg"
        />
    );

    if (!showName) {
        return icon;
    }

    return (
        <div className="flex items-center gap-3.5">
            {icon}
            <div className="leading-tight">
                <p className="text-sm font-semibold tracking-tight text-[var(--garage-text)]">GarageOS</p>
                {compact && (
                    <p className="text-[11px] text-[var(--garage-text-muted)]">Операционная платформа</p>
                )}
            </div>
        </div>
    );
}
