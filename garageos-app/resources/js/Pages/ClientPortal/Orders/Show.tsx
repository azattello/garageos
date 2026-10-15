import { Head } from '@inertiajs/react';
import ClientPortalLayout from '../../../Layouts/ClientPortalLayout';
import {
    ClientOrderDetails,
    type ClientOrderProps,
} from '../../PublicOrder/Show';

type Props = {
    clientName: string;
    organizationName: string;
    order: ClientOrderProps['order'];
};

export default function Show({ clientName, organizationName, order }: Props) {
    return (
        <ClientPortalLayout
            clientName={clientName}
            organizationName={organizationName}
        >
            <Head title={`Заказ ${order.number}`} />
            <ClientOrderDetails order={order} showServiceBrand={false} />
        </ClientPortalLayout>
    );
}
